<?php

error_reporting(E_ALL);

use Slim\App;
use \Slim\Views\PhpRenderer;
use \Kreait\Firebase\Factory;
use \Kreait\Firebase\ServiceAccount;
use \Monolog\Handler\StreamHandler;
use Aws\Sdk as AWS;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Exception\AwsException;

return function (App $app) {
    $container = $app->getContainer();

    // view renderer
    $container['renderer'] = function ($c) {
        $settings = $c->get('settings')['renderer'];
        return new PhpRenderer($settings['template_path']);
    };

    // monolog
    $container['logger'] = function ($c) {
        $settings = $c->get('settings')['logger'];
        $logger = new \Monolog\Logger($settings['name']);
        //$logger->pushProcessor(new \Monolog\Processor\IntrospectionProcessor());
        $logger->pushHandler(
            new StreamHandler(
                $settings['path'],
                $settings['level']
            )
        );
        return $logger;
    };

    // firebase
    $container['firebase'] = function ($c) {
        $settings = $c->get('settings')['firebase'];
        $log = $c->get('logger');
        //$serviceAccount = ServiceAccount::fromValue($settings['sa_data']);
        $c->get('logger')->info($settings['ServiceAccount']);
        $serviceAccount = ServiceAccount::fromValue($settings['ServiceAccount']);

        $firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri($settings['DatabaseURL'])
            ->createDatabase();
        return $firebase;
    };

    $container['aws'] = function ($c) {
        $settings = $c->get('settings')['AWS'];
        $sdk = new AWS([
            'region'   => $settings['region'],
            'version'  => 'latest',
            'DynamoDb' => [
                'region' => 'eu-central-1'
            ]
        ]);
        return $sdk;
    };

    $container['dynamoDB'] = function ($c) {
        $settings = $c->get('settings')['AWS'];
        $db = new DynamoDbClient([
            'region'   => $settings['region'],
            'version'  => 'latest',
            'DynamoDb' => [
                'region' => $settings['region']
            ]
        ]);
        return $db;
    };

    // 403 handler
    $container['notAllowedHandler'] = function ($c) {
        return function ($request, $response) use ($c) {
            $c->get('logger')->error('403, somehow...', $request, $response);
            return $response->withStatus(403)
                ->withHeader('Content-Type', 'text/html')
                ->write('403 - Not Allowed <br />I don\'t even know how you did that.');
        };
    };

    // 404 handler
    $container['notFoundHandler'] = function ($c) {
        return function ($request, $response) use ($c) {
            $c->get('logger')->error('404', $request);
            return $response->withStatus(404)
                ->withHeader('Content-Type', 'text/html')
                ->write('404 - Page not found');
        };
    };

    // 405 handler
    $container['notAllowedHandler'] = function ($c) {
        return function ($request, $response, $methods) use ($c) {
            $c->get('logger')->error('405', $request);
            return $response->withStatus(405)
                ->withHeader('Allow', implode(', ', $methods))
                ->withHeader('Content-type', 'text/html')
                ->write('Method must be one of: ' . implode(', ', $methods));
        };
    };

    // 500 handler
    $container['errorHandler'] = function ($c) {
        return function ($request, $response, Exception $exception) use ($c) {
            $c->get('logger')->error('500, somehow...');
            $stack = $exception->getTraceAsString();
            $stack = str_replace("\n", '<br />', $stack);
            return $response->withStatus(500)
                ->withHeader('Content-Type', 'text/html')
                ->write('Something went wrong! <br /> I\'m impressed! Something broke in a way that SOME of the page still works.')
                ->write($exception->getMessage() . '<br /')
                ->write($stack);
        };
    };
};
