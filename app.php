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
            'title' => 'Чичерина. Главная',
        ]);
    })
    ->bind('home');

$app
    ->get('/афиша', function () use ($app) {
        $sql = 'SELECT * FROM tours';
        $tours = $app['db']->fetchAll($sql);
        return $app['twig']->render('tour.twig', [
            'tours' => $tours,
            'title' => 'Афиша',
        ]);
    })
    ->bind('tour');

$app
    ->get('/группа', function () use ($app) {
        $sql = 'SELECT * FROM band';
        $band = $app['db']->fetchAll($sql);
        return $app['twig']->render('band.twig', [
            'band' => $band,
            'title' => 'Группа',
        ]);
    })
    ->bind('band');

$app
    ->get('/фото', function () use ($app) {
        $getAlbumsQuery = "SELECT * FROM albums";
        $albums = $app['db']->fetchAll($getAlbumsQuery);

        return $app['twig']->render('photos.twig', [
            'albums' => $albums,
            'title' => 'Фото',
        ]);
    })
    ->bind('photos');

$app
    ->get('/райдер', function () use ($app) {
        $rider = $app['db']->fetchAll("SELECT * FROM rider");

        return $app['twig']->render('rider.twig', [
            'title' => 'Райдер',
            'riders' => $rider,
        ]);
    })
    ->bind('rider');

$app
    ->get('/контакты', function () use ($app) {
		$contacts = $app['db']->fetchAll("SELECT * FROM contacts");
        return $app['twig']->render('contacts.twig', [
            'title' => 'Контакты',
			'contacts' => $contacts,
        ]);
    })
    ->bind('contacts');

$app
    ->get('/фото/альбом/{id}', function ($id) use($app) {
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

        $tours = $app['db']->fetchAll("SELECT * from tours");
        $albums = $app['db']->fetchAll("SELECT * FROM albums");
        $rider = $app['db']->fetchAll("SELECT * FROM rider");
		$contacts = $app['db']->fetchAll("SELECT * FROM contacts");
        
        return $app['twig']->render('dashboard.twig', [
            'title' => 'Админинстрирование сайта',
            'logo' => 'Управление сайтом',
            'tours' => $tours,
            'albums' => $albums,
            'riders' => $rider,
			'contacts' => $contacts,
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
                'type' => 'danger',
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

    foreach ($files['name'] as $f => $name) {
        if ($files['error'][$f] == 4) {
            continue; // Skip file if any error found
        }
        if ($files['error'][$f] == 0) {
            if ($files['size'][$f] > $max_file_size) {
                $message = "$name is too large!.";
                continue; // Skip large files
            } elseif (!in_array(pathinfo($name, PATHINFO_EXTENSION), $valid_formats)) {
                $message = "$name is not a valid format";
                continue; // Skip invalid file formats
            } else { // No error found! Move uploaded files
                if (move_uploaded_file($files["tmp_name"][$f], __DIR__ . $path . $name))
               $message[] = $path . $name;
            }
        }
    }

    return $message;
}

$app
    ->post('/upload_photo', function () use ($app) {

        $albumId = $_POST['albumId'];
        $valid_formats = $app['config']['file.upload']['valid_formats'];
        $max_file_size = intval($app['config']['file.upload']['max_file_size']);
        $path = $app['config']['file.upload']['path'];

        $file_upload = uploadFiles($_FILES['photos'], $max_file_size,$valid_formats, $path);
        
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
        } else {
            return $app->redirect($app["url_generator"]->generate("album", ['id' => $albumId, 'error' => 'error']));
        }
    })
    ->bind('upload_photo');

$app
    ->post('/delete_photo', function () use($app) {
        $id = $_POST['deletePhoto'];
        $albumId = $_POST['albumId'];
        $file = $app['db']->fetchAssoc("SELECT name FROM photos WHERE id = '$id'");
        try {
            $app['db']->delete('photos', ['id' => $id]);
            unlink(__DIR__ . $file['name']);
            return $app->redirect($app["url_generator"]->generate("album", ['id' => $albumId]));
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    })
    ->bind('delete_photo');

$app
    ->post('/delete_album', function (Request $request) use($app) {
        $albumId = $request->get('id');
        $status[] = null;
        $photos = $app['db']->fetchAll("SELECT * FROM photos WHERE album_id = '$albumId'");

        if (!empty($photos)) {
            $status = [
                'type' => 'warning',
                'message' => 'В альбоме есть фотографии',
            ];
            return new JsonResponse($status);
        } else {
            $app['db']->delete('albums', ['id' => $albumId]);
            $status = [
                'type' => 'success',
                'message' => 'Альбом успешно удален',
            ];
            return new JsonResponse($status);
        }
    })
    ->bind('delete_album');

$app
    ->post('/add_rider', function () use($app){
        $valid_formats = ['pdf', 'doc', 'docx'];
        $max_file_size = 1024000;
        $path = '/web/media/rider/';

        $file_upload = uploadFiles($_FILES['rider'], $max_file_size,$valid_formats, $path);
        if (is_array($file_upload)) {

            for ($i = 0; $i < count($file_upload); $i++) {
                $app['db']->insert(
                    'rider', [
                        'path' => $file_upload[$i],
                        'date' => date('Y-m-d'),
                    ]
                );
            }
            return $app->redirect($app["url_generator"]->generate("dashboard"));
        } else {
            return $app->redirect($app["url_generator"]->generate("dashboard"));
        }
    })
    ->bind('add_rider');

$app
    ->post('/delete_rider', function (Request $request) use ($app) {
        $id = $request->get('id');
        $status[] = null;
        $file = $app['db']->fetchAssoc("SELECT path FROM rider WHERE id = '$id'");

        try {
            $app['db']->delete('rider', ['id' => $id]);
            unlink(__DIR__ . $file['path']);
            $status = [
                'type' => 'success',
                'message' => 'Документ успешно удален',
            ];
            return new JsonResponse($status);
        } catch (\Exception $e) {
            $status = [
                'type' => 'warning',
                'message' => $e->getMessage(),
            ];
            return new JsonResponse($status);
        }


    })
    ->bind('delete_rider');

$app
	->post('/add_contact', function (Request $request) use($app) {
		$position = trim($request->get('position'));
		$firstname = trim($request->get('firstname'));
		$lastname = trim($request->get('lastname'));
		$phone = trim($request->get('phone'));
		$email = trim($request->get('email'));
		$status[] = '';

		try {
			$app['db']->insert(
				'contacts', [
					'position' => $position,
					'firstname' => $firstname,
					'lastname' => $lastname,
					'phone' => $phone,
					'email' => $email,
				]
			);

			$status = [
				'type' => 'success',
				'message' => 'Контакт успешно добавлен',
			];
			return new JsonResponse($status);

		} catch (\Exception $e) {
			$status = [
				'type' => 'danger',
				'message' => $e->getMessage(),
			];
			return new JsonResponse($status);
		}
	})
	->bind('add_contact');

$app
    ->post('/edit_contact', function () use($app) {
        try {
            $app['db']->update(
                'contacts', [
                    'position' => trim($_POST['position']),
                    'firstname' => trim($_POST['firstname']),
                    'lastname' => trim($_POST['lastname']),
                    'phone' => trim($_POST['phone']),
                    'email' => trim($_POST['email']),
                ], [
                    'id' => $_POST['id'],
                ]
            );
            return $app->redirect($app["url_generator"]->generate("dashboard"));
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    })
    ->bind('edit_contact');

$app
	->post('/delete_contact', function () use($app) {
		$id = $_POST['contact_id'];
		$app['db']->delete('contacts', ['id'=>$id]);
		return $app->redirect($app["url_generator"]->generate('dashboard'));
	})
	->bind('delete_contact');

$app->run();