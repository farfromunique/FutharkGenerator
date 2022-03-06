<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
  $logger = $app->getContainer()->get('logger');

  $app->add(function (Request $request, Response $response, callable $next) use ($logger) {
    $consent = $request->getCookieParam('FutharkConsent', 'not found');
    if ($consent === 'granted') {
      $request = $request->withAttribute('CookieConsent', true);
    }
    $response = $next($request, $response);

    return $response;
  });
};
