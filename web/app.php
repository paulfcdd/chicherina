<?php
include_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;
$app
    ->register(new Silex\Provider\AssetServiceProvider(), [
        'assets.version' => 'v1',
        'assets.version_format' => '%s?version=%s',
        'assets.named_packages' => array(
            'css' => [
                        'version' => 'css2',
                        'base_path' => __DIR__ . '/css',
                    ],
            'images' => [
                        'base_path' => __DIR__ . '/img'
            ],
        ),
    ])
    ->register(new Silex\Provider\MonologServiceProvider(), [
        'monolog.logfile' => __DIR__ . '/../logs/dev.log'
    ])
    ->register(new Silex\Provider\TwigServiceProvider(), [
        'twig.path' => __DIR__ . '/../tpl',
    ]);

$app
    ->get('/', function () use ($app) {
        return $app['twig']->render('index.twig', [
            'test' => 'Testowy hello',
        ]);
    })
    ->bind('home');

$app->run();