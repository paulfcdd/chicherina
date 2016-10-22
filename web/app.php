<?php
include_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/../app/services/UserProvider.php';

use Services\UserProvider;
use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['debug'] = true;
$app
    ->register(new Silex\Provider\SessionServiceProvider())
    ->register(new Silex\Provider\SecurityServiceProvider(), [
        'security.firewalls' => [
            'root' => array(
                'pattern' => '^/root',
                'form' => array('login_path' => '/login', 'check_path' => '/root/login_check'),
                'users' => array(
                    'root' => array('ROLE_ROOT', password_hash('ctrnjh', PASSWORD_BCRYPT)),
                ),
                'logout' => [
                    'logout_path' => '/root/logout',
                    'invalidate_session' => true
                ],
            ),
        ],
    ])
    ->register(new Rpodwika\Silex\YamlConfigServiceProvider(__DIR__ . '/../config/parameters.yml'))
    ->register(new Silex\Provider\DoctrineServiceProvider(), [
        'db.options' => [
            'driver' => $app['config']['database']['driver'],
            'host' => $app['config']['database']['host'],
            'user' => $app['config']['database']['db_user'],
            'dbname' => $app['config']['database']['db_name'],
            'password' => $app['config']['database']['db_password'],
            'charset' => 'utf8mb4',
        ],
    ])
    ->register(new Silex\Provider\AssetServiceProvider(), [
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

$app->boot();

$app
    ->get('/', function () use ($app) {
        return $app['twig']->render('index.twig', [
            'title' => 'Чичерина',
        ]);
    })
    ->bind('home');

$app
    ->get('/tour', function () use ($app) {
        $sql = 'SELECT * FROM tours';
        $tours = $app['db']->fetchAll($sql);
        return $app['twig']->render('tour.twig', [
            'tours' => $tours,
        ]);
    })
    ->bind('tour');

$app
    ->get('/band', function () use ($app) {
        $sql = 'SELECT * FROM band';
        $band = $app['db']->fetchAll($sql);
        return $app['twig']->render('band.twig', [
            'band' => $band,
        ]);
    })
    ->bind('band');

$app
    ->get('/photos', function () use ($app) {
        return $app['twig']->render('photos.twig', [

        ]);
    })
    ->bind('photos');

$app
    ->get('/contacts', function () use ($app) {
        return $app['twig']->render('contacts.twig', [

        ]);
    })
    ->bind('contacts');

$app
    ->get('/rider', function () use ($app) {
        return $app['twig']->render('rider.twig', [

        ]);
    })
    ->bind('rider');

$app
    ->get('/root', function () use ($app) {
        $sql = "SELECT * FROM `users` WHERE role = 'ROLE_ADMIN'";
        $admins = $app['db']->fetchAll($sql);
        return $app['twig']->render('root.twig', [
            'admins' => $admins,
        ]);
    })
    ->bind('root');

$app
    ->get('/add_admin', function () use ($app) {
        var_dump($_POST);
    })
    ->bind('add_admin');

$app
    ->get('/login', function (Request $request) use ($app) {
        return $app['twig']->render('login.twig', [
            'error'         => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
        ]);
    })
    ->bind('login');

$app->run();