<?php

use ACWPD\Futhark\Power;
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
  $container = $app->getContainer();
  $meta = [
    'title' => $container->get('settings')['APP_TITLE'] . $container->get('settings')['APP_VERSION'],
    'version' => $container->get('settings')['APP_VERSION'],
  ];

  $app->get('/', function (Request $request, Response $response) use ($container, $meta) {
    $container->get('logger')->info('Full Power requested');
    $power = new Power($container);
    $power->loadPowersDB();
    $data = $power->withRandomType()
      ->withRandomFlavor()
      ->withRandomTwist()
      ->getPowerData();
    $localVars = $power->getDataForLocalStorage();
    $args = array_merge($data, $meta, ['localVars' => $localVars]);

    $container->get('logger')->info('Power returned: ' . $power->getPowerString());

    return $container
      ->get('renderer')
      ->render($response, 'FullPageBigImages.phtml', $args);
  })->setName('home');

  $app->get('/list', function (Request $request, Response $response) use ($container, $meta) {
    $power = new Power($container);
    $power->loadPowersDB();
    $data = $power->withAllData()
      ->getPowerData();
    $args = array_merge($data, $meta);

    return $container->get('renderer')->render($response, 'List.phtml', $args);
  })->setName('list');

  $app->get('/credits', function (Request $request, Response $response) use ($container, $meta) {
    $container->get('logger')->info('Credits page requested');
    return $container->get('renderer')->render($response, 'Credits.phtml', $meta);
  })->setName('credits');

  $app->get('/load/{type_}/{flavor_}/{twist_}', function (Request $request, Response $response, array $args) use ($container, $meta) {
    $power = new Power($container);
    $power->loadPowersDB();
    $data = $power->withType($args['type_'])
      ->withFlavor($args['flavor_'])
      ->withTwist($args['twist_'])
      ->getPowerData();
    $localVars = $power->getDataForLocalStorage();
    $args = array_merge($data, $meta, ['localVars' => $localVars]);

    return $container->get('renderer')->render($response, 'FullPageBigImages.phtml', $args);
  })->setName('loadDirect');

  $app->get('/load/{id_}', function (Request $request, Response $response, array $args) use ($container, $meta) {
    $db = $container->get('dynamoDB');
    $save_name = $args['id_'];
    $logger = $container->get('logger');
    $logger->info('Power Load requested! Input:' . $save_name);

    $final = [
      'save_name' => ['S' => ''],
      'notes' => [
        'M' => [
          'flavor' => ['S' => ''],
          'type' => ['S' => ''],
          'twist' => ['S' => '']
        ]
      ],
      'power' => [
        'M' => [
          'flavor' => [
            'L' => [
              ['S' => '']
            ]
          ],
          'type' => [
            'L' => [
              ['S' => '']
            ]
          ],
          'twist' => [
            'L' => [
              ['S' => '']
            ]
          ]
        ]
      ]
    ];
    $result = $db->getItem([
      'Key' => [
        'save_name' => ['S' => $save_name],
      ],
      'TableName' => $container->get('settings')['AWS']['dynamoDB']['tableName']
    ]);

    $result_data = $result['Item'];
    $power_data = [
      'flavor' => $result_data['power']['M']['flavor']['L'],
      'type'   => $result_data['power']['M']['type']['L'],
      'twist'  => $result_data['power']['M']['twist']['L']
    ];

    $power = new Power($container);
    $power->loadPowersDB();

    foreach ($power_data['type'] as $key => $value) {
      $power->withType($value['S']);
    }

    foreach ($power_data['flavor'] as $key => $value) {
      $power->withFlavor($value['S']);
    }

    foreach ($power_data['twist'] as $key => $value) {
      $power->withTwist($value['S']);
    }

    $details = $power->getPowerData();

    $args = array_merge($args, $meta, $details);

    return $container->get('renderer')->render($response, 'FullPageBigImages.phtml', $args);
  })->setName('loadSaved');

  $app->post('/save', function (Request $request, Response $response) use ($container) {
    $db = $container->get('dynamoDB');
    $inputs = $request->getParsedBody();
    $logger = $container->get('logger');
    $logger->info('Power Save requested! Input:', $inputs);

    $current_url = $inputs['ic-current-url'] ?? $request->getUri();

    $matches = [];

    if (preg_match('/\/load\/([A-Za-z0-9\-\_\=]+)$/', $current_url, $matches) == 1) {
      $save_name = $matches[1];
    } else {
      $save_name = \ACWPD\Helpers\SequentialKey::generate();
    }

    $logger->info('Save name: ' . $save_name);

    $final = [
      'save_name' => ['S' => ''],
      'notes' => [
        'M' => [
          'flavor' => ['S' => ''],
          'type' => ['S' => ''],
          'twist' => ['S' => '']
        ]
      ],
      'power' => [
        'M' => [
          'flavor' => [
            'L' => [
              ['S' => '']
            ]
          ],
          'type' => [
            'L' => [
              ['S' => '']
            ]
          ],
          'twist' => [
            'L' => [
              ['S' => '']
            ]
          ]
        ]
      ]
    ];

    $final['save_name']['S'] = $save_name;

    $final['notes']['M']['flavor']['S'] = $inputs['notes_flavor'] ?? '';
    $final['notes']['M']['twist']['S']  = $inputs['notes_twist']  ?? '';
    $final['notes']['M']['type']['S']   = $inputs['notes_type']   ?? '';

    foreach ($inputs as $key => $value) {
      if (substr($key, 0, 6) == 'power_') {
        switch (\explode('_', $key)[1]) {
          case 'flavor':
            $final['power']['M']['flavor']['L'][] = ['S' => $value];
            break;

          case 'twist':
            $final['power']['M']['twist']['L'][] = ['S' => $value];
            break;

          case 'type':
            $final['power']['M']['type']['L'][] = ['S' => $value];
            break;
        }
      }
    }

    $result = $db->putItem([
      'Item' => $final,
      'ReturnValues' => 'ALL_OLD',
      'TableName' => $container->get('settings')['AWS']['dynamoDB']['tableName']
    ]);

    $logger->info('Result: ' . $result);

    $path = $container
      ->get('router')
      ->pathFor(
        'loadSaved',
        ['id_' => $save_name]
      );

    $response = $response->withHeader('X-IC-PushURL', $path);
    return $container->get('renderer')->render($response, 'blank.phtml', ['savekey' => $key]);
  })->setName('save');

  $app->group('/a', function (App $app) use ($container) {
    $app->group('/roll', function (App $app) use ($container) {
      $app->get('/', function (Request $request, Response $response) use ($container) {
        $power = new Power($container);
        $power->loadPowersDB();
        $data = $power->withRandomType()
          ->withRandomFlavor()
          ->withRandomTwist()
          ->getPowerData();
        $localVars = $power->getJSONforLocalStorage();
        $args = array_merge($data, ['localVars' => $localVars]);

        $container->get('logger')->info('Power returned: ' . $power->getPowerString());

        return $container->get('renderer')->render($response, 'BigImages.phtml', $args);
      })->setName('ajax_full');

      $app->get('/type', function (Request $request, Response $response) use ($container) {
        $container->get('logger')->info('/a/roll/type requested');
        $power = new Power($container);
        $power->loadPowersDB();
        $data = $power->withRandomType()
          ->getPowerData();
        $localVars = $power->getJSONforLocalStorage();
        $args = array_merge($data);

        $out = $container
          ->get('renderer')
          ->render($response, 'OnlyType.phtml', $args)
          ->withHeader('X-IC-Set-Local-Vars', $localVars);

        $container->get('logger')->info('Power Type returned: ' . $power->getPowerData()['type'][0]['name']);
        return $out;
      })->setName('ajax_type');

      $app->get('/flavor', function (Request $request, Response $response) use ($container) {
        $power = new Power($container);
        $power->loadPowersDB();
        $data = $power->withRandomFlavor()
          ->getPowerData();
        $localVars = $power->getJSONforLocalStorage();
        $args = array_merge($data);

        $out = $container
          ->get('renderer')
          ->render($response, 'OnlyFlavor.phtml', $args)
          ->withHeader('X-IC-Set-Local-Vars', $localVars);

        $container->get('logger')->info('Power Flavor returned: ' . $power->getPowerData()['flavor'][0]['name']);
        return $out;
      })->setName('ajax_flavor');

      $app->get('/twist', function (Request $request, Response $response) use ($container) {
        $container->get('logger')->info('/a/roll/twist requested');
        $power = new Power($container);
        $power->loadPowersDB();
        $data = $power
          ->withRandomTwist()
          ->getPowerData();
        $localVars = $power
          ->getJSONforLocalStorage();
        $args = array_merge($data);

        $out = $container
          ->get('renderer')
          ->render($response, 'OnlyTwist.phtml', $args)
          ->withHeader('X-IC-Set-Local-Vars', $localVars);

        $container->get('logger')->info('Power Twist returned: ' . $power->getPowerData()['twist'][0]['cardname']);
        return $out;
      })->setName('ajax_twist');
    });

    $app->get('/load/{type_}/{flavor_}/{twist_}', function (Request $request, Response $response, array $args) use ($container) {
      $power = new Power($container);
      $power->loadPowersDB();
      $data = $power->withType($args['type_'])
        ->withFlavor($args['flavor_'])
        ->withTwist($args['twist_'])
        ->getPowerData();
      $args = array_merge($data);

      return $container->get('renderer')->render($response, 'BigImages.phtml', $args);
    })->setName('ajax_load');
  });
};
