<?php

use ACWPD\Futhark\FutharkRouter;
use Slim\App;

return function (App $app) {
  $router = new FutharkRouter($app->getContainer());
  $app->get('/', $router::class . ':getRoot')->setName('home');

  $app->get('/list', $router::class . ':getList')->setName('list');

  $app->get('/credits', $router::class . ':getCredits')->setName('credits');

  $app->get('/load/{id_}', $router::class . ':getByID')->setName('loadSaved');

  $app->get('/load/{type_}/{flavor_}/{twist_}', $router::class . ':getByContent')->setName('loadDirect');

  $app->post('/save', $router::class . ':postSave')->setName('save');

  $app->group('/a', function (App $app) use ($router) {
    $app->group('/roll', function (App $app) use ($router) {
      $app->get('/', $router::class . ':getAjaxRoot')->setName('ajax_full');

      $app->get('/type', $router::class . ':getAjaxType')->setName('ajax_type');

      $app->get('/flavor', $router::class . ':getAjaxFlavor')->setName('ajax_flavor');

      $app->get('/twist', $router::class . ':getAjaxTwist')->setName('ajax_twist');
    });

    $app->post('/consent', $router::class . ':postConsent');
  });
};
