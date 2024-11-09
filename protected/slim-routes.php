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

    /*
    $app->get('/donate', function (Request $request, Response $response) use ($container, $meta) {
        return $container->get('renderer')->render($response, 'Donate.phtml', $meta);
    })->setName('donate');
    */

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
        $db = $container->get('firebase')->getDatabase();
        $ref = $db->getReference('saved/' . $args['id_']);
        $data = $ref->getValue();
        if (is_null($data)) {
            $container->get('logger')->error('Save ID ' . $args['id_'] . ' not found.');
            throw new \Exception('Save ID ' . $args['id_'] . ' not found.', 1);
        }

        foreach ($data as $key => $value) {
            if (substr($key, 0, 6) == 'power_') {
                $power_data[explode('_', $key)[1]][] = $value;
            }
        }

        $power = new Power($container);
        $power->loadPowersDB();

        foreach ($power_data['type'] as $key => $value) {
            $power->withType($value);
        }

        foreach ($power_data['flavor'] as $key => $value) {
            $power->withFlavor($value);
        }

        foreach ($power_data['twist'] as $key => $value) {
            $power->withTwist($value);
        }

        $details = $power->getPowerData();

        $args = array_merge($args, $meta, $details);

        return $container->get('renderer')->render($response, 'FullPageBigImages.phtml', $args);
    })->setName('loadSaved');

    $app->post('/save', function (Request $request, Response $response) use ($container) {
        $inputs = $request->getParsedBody();
        $logger = $container->get('logger');
        $logger->info('Power Save requested!');
        $logger->info('Input:', $inputs);

        $powerParts = [];
        foreach ($inputs as $key => $value) {
            if (substr($key, 0, 6) == 'power_') {
                $powerParts[$key] = $value;
            }
        }
        $powerParts['notes']['type'] = $inputs['notes_type'] ?? '';
        $powerParts['notes']['flavor'] = $inputs['notes_flavor'] ?? '';
        $powerParts['notes']['twist'] = $inputs['notes_twist'] ?? '';

        $db = $container->get('firebase')->getDatabase();
        $matches = [];
        if (isset($inputs['ic-current-url']) && preg_match('/\/load\/([A-Za-z0-9\-\_\=]+)$/', $inputs['ic-current-url'], $matches) == 1) {
            $saved = $db
                ->getReference('saved/' . $matches[1])
                ->set($powerParts);
        } else {
            $saved = $db
                ->getReference('saved')
                ->push($powerParts);
        }
        $key = $saved->getKey();

        $path = $container->get('router')->pathFor('loadSaved', [
            'id_' => $key
        ]);
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
