<?php
include_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/app/services/UserProvider.php';

use Services\UserProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app['debug'] = true;
$app
    ->register(new Rpodwika\Silex\YamlConfigServiceProvider(__DIR__ . '/config/parameters.yml'))
    ->register(new Silex\Provider\SessionServiceProvider())
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
                'base_path' => __DIR__ . '/web/css',
            ],
            'images' => [
                'base_path' => __DIR__ . '/web/img',
            ],
            'js' => [
                'base_path' => __DIR__ . '/web/js',
            ],
        ),
    ])
    ->register(new Silex\Provider\SecurityServiceProvider(), [
        'security.firewalls' => [
            'root' => array(
                'pattern' => $app['config']['root.setup']['pattern'],
                'form' => array(
                    'login_path' => $app['config']['root.setup']['login_path'],
                    'check_path' => $app['config']['root.setup']['check_path'],
                    'always_use_default_target_path' => true,
                    'default_target_path' => $app['config']['root.setup']['redirect_path']
                ),
                'users' => array(
                    $app['config']['root.setup']['username'] => array(
                        $app['config']['root.setup']['role'],
                        password_hash($app['config']['root.setup']['password'], PASSWORD_BCRYPT)
                    ),
                ),
                'logout' => [
                    'logout_path' => $app['config']['root.setup']['logout_path'],
                    'invalidate_session' => true
                ],
            ),
            'admin' => [
                'pattern' => '^/dashboard',
                'form' => [
                    'login_path' => '/admin',
                    'check_path' => '/dashboard/login_check',
                    'always_use_default_target_path' => true,
                    'default_target_path' => '/dashboard',
                ],
                'logout' => [
                    'logout_path' => '/dashboard/logout',
                    'invalidate_session' => true
                ],
                'users' => function () use ($app) {
                    return new UserProvider($app['db']);
                }
            ],
        ],
    ])
    ->register(new Silex\Provider\MonologServiceProvider(), [
        'monolog.logfile' => __DIR__ . '/logs/dev.log'
    ])
    ->register(new Silex\Provider\TwigServiceProvider(), [
        'twig.path' => __DIR__ . '/tpl',
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
            'title' => 'Войти как root'
        ]);
    })
    ->bind('root');

$app
    ->post('/add_admin', function (Request $request) use ($app) {

        $status[] = '';
        $login = trim($request->get('login'));
        $email = trim($request->get('email'));
        $password = password_hash($request->get('password'), PASSWORD_BCRYPT);
        $role = trim($request->get('role'));

        $findUsername = "SELECT username FROM users WHERE username = '$login'";
        $findEmail = "SELECT email FROM users WHERE email = '$email'";
        $username = $app['db']->fetchAll($findUsername);
        $emailData = $app['db']->fetchAll($findEmail);

        if (!empty($username) or !empty($emailData)) {
            $status = [
                'type' => 'warning',
                'message' => 'Пользователь с данными ' . $login . '/' . $email . ' существует',
            ];
        } else {
            try {
                $app['db']->insert('users', [
                    'username' => $login,
                    'email' => $email,
                    'password' => $password,
                    'created' => date('Y-m-d'),
                    'role' => 'ROLE_' . $role,
                ]);
                $status = [
                    'type' => 'success',
                    'message' => 'Пользователь ' . $login . ' успешно создан',
                ];
            } catch (\Exception $e) {
                $status = [
                    'type' => 'danger',
                    'message' => $e->getMessage(),
                ];
            }
        }
        return new JsonResponse($status);
    })
    ->bind('add_admin');

$app
    ->get('/login', function (Request $request) use ($app) {
        return $app['twig']->render('login.twig', [
            'error' => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
            'title' => 'Войти как Root'
        ]);
    })
    ->bind('login');

$app
    ->get('/admin', function (Request $request) use ($app) {
        return $app['twig']->render('admin.twig', [
            'error' => $app['security.last_error']($request),
            'last_username' => $app['session']->get('_security.last_username'),
            'title' => 'Войти как администратор',
        ]);
    })
    ->bind('admin');

$app
    ->get('/dashboard', function () use ($app) {
        $tourQuery = "SELECT * from tours";
        $tours = $app['db']->fetchAll($tourQuery);
        return $app['twig']->render('dashboard.twig', [
            'title' => 'Админинстрирование сайта',
            'logo' => 'Управление сайтом',
            'tours' => $tours,
        ]);
    })
    ->bind('dashboard');

$app
    ->post('/delete_admin', function () use ($app) {
        $id = $_POST['admin_id'];
        try {
            $app['db']->delete('users', ['id' => $id]);
            return $app->redirect($app["url_generator"]->generate("root"));
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    })
    ->bind('delete_admin');

$app
    ->post('/edit_admin', function() use($app) {
        return new \Symfony\Component\HttpFoundation\Response('kek');
    })
    ->bind('edit_admin');

$app->run();