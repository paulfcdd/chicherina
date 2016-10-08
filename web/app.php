<?php
include_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

$app
    ->register(new Silex\Provider\TwigServiceProvider(), [
        'twig.path' => __DIR__ . '/../tpl',
    ]);

$app->get('/', function () use ($app) {
        return $app['twig']->render('index.html.twig', [
            'test' => 'Testowy hello',
        ]);
    })
    ->bind('home');

$app->run();