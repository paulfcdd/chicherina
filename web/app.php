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
                'base_path' => __DIR__ . '/css',
                    ],
            'images' => [
                'base_path' => __DIR__ . '/img',
            ],
            'js' => [
                'base_path' => __DIR__ . '/js',
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

$app
    ->get('/news', function() use($app) {
        return $app['twig']->render('news.twig', [
            
        ]);
    })
    ->bind('news');

$app
    ->get('/band', function () use($app){
        return $app['twig']->render('band.twig', [
            
        ]);
    })
    ->bind('band');

$app
    ->get('/media', function () use($app){
        return $app['twig']->render('media.twig', [

        ]);
    })
    ->bind('media');

$app
    ->get('/press', function () use($app){
        return $app['twig']->render('press.twig', [

        ]);
    })
    ->bind('press');

$app
    ->get('/contacts', function () use($app){
        return $app['twig']->render('contacts.twig', [

        ]);
    })
    ->bind('contacts');

$app
    ->get('/rider', function () use($app){
        return $app['twig']->render('rider.twig', [

        ]);
    })
    ->bind('rider');

$app->run();