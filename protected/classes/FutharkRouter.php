<?php

namespace ACWPD\Futhark;

use Psr\Container\ContainerInterface;
use ACWPD\Futhark\Power;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\PhpRenderer;
use Monolog\Logger;

class FutharkRouter {
  private ContainerInterface $container;
  private Logger $logger;
  private array $meta;
  protected PhpRenderer $renderer;

  public function __construct(ContainerInterface $container) {
    $this->container = $container;
    $this->logger = $container->get('logger');
    $ver = $container->get('settings')['APP_VERSION'];
    $title = $container->get('settings')['APP_TITLE'];
    $full_title = $title . $ver;
    $this->meta = [
      'title' => $full_title,
      'version' => $ver,
    ];
    $this->renderer = $container->get('renderer');
  }

  /* Sugar */
  private function render(Response $response, $template, array $data = []): Response {
    return $this->renderer->render($response, $template, $data);
  }

  /* Maps to `GET /` */
  public function getRoot(Request $request, Response $response): Response {
    $this->logger->info('Full Power requested');
    $consent = $request->getAttribute('CookieConsent', false);
    $power = new Power($this->container);
    $data = $power->withRandomType()
      ->withRandomFlavor()
      ->withRandomTwist()
      ->getPowerData();
    $localVars = $power->getDataForLocalStorage();
    $this->logger->info('localvars', $localVars);
    $args = array_merge($data, $this->meta, $localVars, ['cookie_consent' => $consent]);

    $this->logger->info('Power returned: ' . $power->getPowerForLog());

    return $this->renderer->render($response, 'FullPageBigImages.phtml', $args);
  }

  /* Maps to `GET /a/roll/` */
  public function getAjaxRoot(Request $request, Response $response): Response {
    $this->logger->info('Full Ajax Power requested');
    $power = new Power($this->container);
    $data = $power->withRandomType()
      ->withRandomFlavor()
      ->withRandomTwist()
      ->getPowerData();
    $localVars = $power->getJSONforLocalStorage();
    $args = array_merge($data, ['localVars' => $localVars]);

    $this->logger->info('Power returned: ' . $power->getPowerForLog());

    return $this->render($response, 'BigImages.phtml', $args);
  }

  /* Maps to `GET /a/roll/type` */
  public function getAjaxType(Request $request, Response $response): Response {
    $this->logger->info('/a/roll/type requested');
    $power = new Power($this->container);
    $data = $power->withRandomType()
      ->getPowerData();
    $localVars = $power->getJSONforLocalStorage();
    $args = array_merge($data);
    $this->logger->info('Power Type returned: ' . $power->getPowerData()['type'][0]['name']);
    $this->logger->debug('    localStorage: ', [$localVars]);

    return $this->render($response, 'OnlyType.phtml', $args)
      ->withHeader('X-IC-Set-Local-Vars', $localVars);
  }

  /* Maps to `GET /a/roll/flavor` */
  public function getAjaxFlavor(Request $request, Response $response): Response {
    $this->logger->info('/a/roll/flavor requested');
    $power = new Power($this->container);
    $data = $power->withRandomFlavor()
      ->getPowerData();
    $localVars = $power->getJSONforLocalStorage();
    $args = array_merge($data);

    $this->logger->info('Power Flavor returned: ' . $power->getPowerData()['flavor'][0]['name']);
    return $this->render($response, 'OnlyFlavor.phtml', $args)
      ->withHeader('X-IC-Set-Local-Vars', $localVars);
  }

  /* Maps to `GET /a/roll/twist` */
  public function getAjaxTwist(Request $request, Response $response): Response {
    $this->logger->info('/a/roll/twist requested');
    $power = new Power($this->container);
    $data = $power->withRandomTwist()
      ->getPowerData();
    $localVars = $power->getJSONforLocalStorage();
    $args = array_merge($data);

    $this->logger->info('Power Twist returned: ' . $power->getPowerData()['twist'][0]['cardname']);
    return $this->render($response, 'OnlyTwist.phtml', $args)
      ->withHeader('X-IC-Set-Local-Vars', $localVars);
  }

  /* Maps to `GET /list` */
  public function getList(Request $request, Response $response): Response {
    $this->logger->info('Full listing requested');
    $consent = $request->getAttribute('CookieConsent', false);
    $power = new Power($this->container);
    $data = $power->withAllData()
      ->getPowerData();
    $args = array_merge($data, $this->meta, ['cookie_consent' => $consent]);

    return $this->render($response, 'List.phtml', $args);
  }

  /* Maps to `GET /credits` */
  public function getCredits(Request $request, Response $response): Response {
    $this->logger->info('Credits page requested');
    $consent = $request->getAttribute('CookieConsent', false);
    $args = array_merge($this->meta, ['cookie_consent' => $consent]);
    return $this->render($response, 'Credits.phtml', $args);
  }

  /* Maps to `GET /load/{id_}` */
  public function getByID(Request $request, Response $response, array $args): Response {
    $consent = $request->getAttribute('CookieConsent', false);
    $db = $this->container->get('dynamoDB');
    $save_name = $args['id_'];
    $this->logger->info('Power Load requested! Input:' . $save_name);

    $result = $db->getItem([
      'Key' => [
        'save_name' => ['S' => $save_name],
      ],
      'TableName' => $this->container->get('settings')['AWS']['dynamoDB']['tableName']
    ]);

    $result_data = $result['Item'];
    $power_data = [
      'flavor' => $result_data['power']['M']['flavor']['L'],
      'type'   => $result_data['power']['M']['type']['L'],
      'twist'  => $result_data['power']['M']['twist']['L']
    ];

    $power = new Power($this->container);

    foreach ($power_data['type'] as $key => $value) {
      $power->withType($value['S']);
    }

    foreach ($power_data['flavor'] as $key => $value) {
      $power->withFlavor($value['S']);
    }

    foreach ($power_data['twist'] as $key => $value) {
      $power->withTwist($value['S']);
    }
    $this->logger->info('Notes', $result['Item']);
    $power->withNotes('type', $result_data['notes']['M']['type']['S'])
      ->withNotes('flavor', $result_data['notes']['M']['flavor']['S'])
      ->withNotes('twist', $result_data['notes']['M']['twist']['S']);

    $details = $power->getPowerData();
    $localVars = $power->getDataForLocalStorage();

    $args = array_merge($args, $this->meta, $details, $localVars, ['cookie_consent' => $consent]);

    return $this->render($response, 'FullPageBigImages.phtml', $args);
  }

  /* Maps to `GET /load/{type_}/{flavor_}/{twist_}` */
  public function getByContent(Request $request, Response $response, array $args): Response {
    $this->logger->info('Static power load requested (' . $args['type_'] . ', ' . $args['flavor_'] . ', ' . $args['twist_'] . ')');
    $consent = $request->getAttribute('CookieConsent', false);
    $power = new Power($this->container);
    $data = $power->withType($args['type_'])
      ->withFlavor($args['flavor_'])
      ->withTwist($args['twist_'])
      ->getPowerData();
    $localVars = $power->getDataForLocalStorage();
    $args = array_merge($data, $this->meta, ['localVars' => $localVars], ['cookie_consent' => $consent]);

    return $this->render($response, 'FullPageBigImages.phtml', $args);
  }

  /* Maps to `POST /save` */
  public function postSave(Request $request, Response $response): Response {
    $db = $this->container->get('dynamoDB');
    $inputs = $request->getParsedBody();
    $this->logger->info('Power Save requested! Input:', $inputs);

    $current_url = $inputs['ic-current-url'] ?? $request->getUri();

    $matches = [];

    if (preg_match('/\/load\/([A-Za-z0-9\-\_\=]+)$/', $current_url, $matches) == 1) {
      $save_name = $matches[1];
    } else {
      $save_name = \ACWPD\Helpers\SequentialKey::generate();
    }

    $this->logger->info('Save name: ' . $save_name);

    $power = new Power($this->container);

    $power->fromInputArray($inputs)->withName($save_name);

    $this->logger->info('Saving power:' . $power->getPowerForLog());

    $result = $db->putItem([
      'Item' => $power->getDataForDynamoDB(),
      'ReturnConsumedCapacity' => 'TOTAL',
      'ReturnValues' => 'ALL_OLD',
      'TableName' => $this->container->get('settings')['AWS']['dynamoDB']['tableName']
    ]);
    if ($result['@metadata']['statusCode'] == 200) {
      $this->logger->info('Capacity consumed: ', $result['ConsumedCapacity']);
    }

    $path = $this->container
      ->get('router')
      ->pathFor(
        'loadSaved',
        ['id_' => $save_name]
      );

    $response = $response->withHeader('X-IC-PushURL', $path);
    return $this->render($response, 'blank.phtml', ['savekey' => $save_name]);
  }

  /* Maps to `POST /a/consent` */
  public function postConsent(Request $request, Response $response): Response {
    $this->logger->info('/a/consent posted');
    $host = $request->getUri()->getHost();
    $exp_domain = \explode('.', $host);
    $domain = implode('.', [$exp_domain[1], $exp_domain[2]]);
    $secure = $request->getUri()->getScheme() === 'https';
    setcookie(
      'FutharkConsent',
      'granted',
      [
        'expires' => (2 ** 31) - 1 /* Set to the 'Epochaylpse' */,
        'path' => '/',
        'domain' => $domain,
        'samesite' => 'Strict',
        'secure' => $secure,
        'httponly' => true
      ]
    );

    return $response;
  }
}
