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
        $getAlbumsQuery = "SELECT * FROM albums";
        $albums = $app['db']->fetchAll($getAlbumsQuery);

        return $app['twig']->render('photos.twig', [
            'albums' => $albums,
        ]);
    })
    ->bind('photos');

$app
    ->get('/photos/album/{id}', function ($id) use($app) {
        $album = $app['db']->fetchAssoc("SELECT * FROM albums WHERE id = '$id'");
        $photos = $app['db']->fetchAll("SELECT * FROM photos WHERE album_id='$id'");
        return $app['twig']->render('singleAlbum.twig', [
            'title' => $album['name'],
            'id' => $id,
            'photos' => $photos,
        ]);
    })
    ->bind('single_album');

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
        $albumsQuery = "SELECT * FROM albums";

        $tours = $app['db']->fetchAll($tourQuery);
        $albums = $app['db']->fetchAll($albumsQuery);
        return $app['twig']->render('dashboard.twig', [
            'title' => 'Админинстрирование сайта',
            'logo' => 'Управление сайтом',
            'tours' => $tours,
            'albums' => $albums,
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
    ->post('/delete_tour', function () use ($app) {
        $id = $_POST['tour_id'];

        try {
            $app['db']->delete('tours', ['id' => $id]);
            return $app->redirect($app["url_generator"]->generate("dashboard"));
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    })
    ->bind('delete_tour');

$app
    ->post('/edit_admin', function () use ($app) {
        return new \Symfony\Component\HttpFoundation\Response('kek');
    })
    ->bind('edit_admin');

$app
    ->post('/add_tour', function (Request $request) use ($app) {
        $status[] = '';
        $date = trim($request->get('date'));
        $city = trim($request->get('city'));
        $place = trim($request->get('place'));

        try {
            $app['db']->insert(
                'tours', [
                'date' => $date,
                'city' => $city,
                'location' => $place,
            ]);
            $status = [
                'type' => 'success',
                'message' => 'Концерт успешно добавлен',
            ];
            return new JsonResponse($status);

        } catch (\Exception $e) {
            $status = [
                'type' => 'success',
                'message' => $e->getMessage(),
            ];
            return new JsonResponse($status);
        }
    })
    ->bind('add_tour');

$app
    ->post('/edit_tour', function (Request $request) use ($app) {
        $status[] = '';
        $id = trim($request->get('id'));
        $date = trim($request->get('date'));
        $city = trim($request->get('city'));
        $place = trim($request->get('place'));

        try {
            $app['db']->update(
                'tours', [
                'date' => $date,
                'city' => $city,
                'location' => $place,
            ], [
                'id' => $id,
            ]);
            $status = [
                'type' => 'success',
                'message' => 'Концерт успешно изменен',
            ];
            return new JsonResponse($status);

        } catch (\Exception $e) {
            $status = [
                'type' => 'success',
                'message' => $e->getMessage(),
            ];
            return new JsonResponse($status);
        }
    })
    ->bind('edit_tour');

$app
    ->post('/add_album', function (Request $request) use ($app) {
        $status[] = '';
        $name = trim($request->get('name'));

        try {
            $app['db']->insert(
                'albums', [
                'name' => $name,
                'date' => date('Y-m-d'),
            ]);
            $status = [
                'type' => 'success',
                'message' => 'Альбом успешно добавлен',
            ];
            return new JsonResponse($status);

        } catch (\Exception $e) {
            $status = [
                'type' => 'success',
                'message' => $e->getMessage(),
            ];
            return new JsonResponse($status);
        }
    })
    ->bind('add_album');

$app
    ->get('/dashboard/album/{id}', function ($id) use ($app) {
        $photosQuery = "SELECT * from photos WHERE album_id = '$id'";
        $photos = $app['db']->fetchAll($photosQuery);
        $albumNameQuery = "SELECT * FROM albums WHERE id='$id'";
        $albumData = $app['db']->fetchAssoc($albumNameQuery);


        return $app['twig']->render('dashboard/photos/album.twig', [
            'id' => $id,
            'logo' => 'Вернуться на главную',
            'photos' => $photos,
            'albumName' => $albumData['name']
        ]);
    })
    ->bind('album');


function uploadFiles(array $files, int $max_file_size, array $valid_formats, string $path) {

    $message = null;

    foreach ($files['photos']['name'] as $f => $name) {
        if ($files['photos']['error'][$f] == 4) {
            continue; // Skip file if any error found
        }
        if ($files['photos']['error'][$f] == 0) {
            if ($files['photos']['size'][$f] > $max_file_size) {
                $message = "$name is too large!.";
                continue; // Skip large files
            } elseif (!in_array(pathinfo($name, PATHINFO_EXTENSION), $valid_formats)) {
                $message = "$name is not a valid format";
                continue; // Skip invalid file formats
            } else { // No error found! Move uploaded files
                if (move_uploaded_file($files["photos"]["tmp_name"][$f], __DIR__ . $path . $name))
               $message[] = $path . $name;
            }
        }
    }

    return $message;
}

$app
    ->post('/upload_photo', function () use ($app) {
        $albumId = $_POST['albumId'];
        $valid_formats = array("jpg", "png", "gif", "zip", "bmp");
        $max_file_size = 1024 * 100; //100 kb
        $path = '/web/media/photos/';

        $file_upload = uploadFiles($_FILES, $max_file_size,$valid_formats, $path);

        var_dump($file_upload);

        if (is_array($file_upload)) {

            for ($i = 0; $i < count($file_upload); $i++) {
                $app['db']->insert(
                    'photos', [
                        'album_id' => $albumId,
                        'name' => $file_upload[$i],
                        'date' => date('Y-m-d'),
                    ]
                );
            }
            return $app->redirect($app["url_generator"]->generate("album", ['id' => $albumId]));
        }
    })
    ->bind('upload_photo');

$app
    ->post('/delete_photo', function () use($app) {
        $id = $_POST['deletePhoto'];
        $albumId = $_POST['albumId'];
        try {
            $app['db']->delete('photos', ['id' => $id]);
            return $app->redirect($app["url_generator"]->generate("album", ['id' => $albumId]));
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    })
    ->bind('delete_photo');

$app->run();