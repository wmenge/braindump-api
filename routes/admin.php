<?php namespace Braindump\Api\Admin;

require_once(__DIR__ . '/../lib/DatabaseFacade.php');
require_once(__DIR__ . '/../model/NotebookFacade.php');
require_once(__DIR__ . '/../model/NoteFacade.php');
require_once(__DIR__ . '/../model/UserFacade.php');

use Braindump\Api\Model\Notebook as Notebook;
use Braindump\Api\Model\Note as Note;
use Cartalyst\Sentry\Users\Paris\User as User;

$dbFacade = new \Braindump\Api\Lib\DatabaseFacade($app, \ORM::get_db());
$notebookFacade = new \Braindump\Api\Model\NotebookFacade();
$noteFacade = new \Braindump\Api\Model\NoteFacade();
$userFacade = new \Braindump\Api\Model\UserFacade();


$app->group('/admin', function () use ($app) {

    $app->get('/login', function () use ($app) {
        $app->render('admin-page.php', [
            'content' => $app->view->fetch('login-fragment.php')
        ]);
    });

    $app->post('/login', function () use ($app) {

        try {
            \Sentry::authenticate($app->request->post());
        } catch (\Exception $e) {
            $app->flashNow('error', $e->getMessage());
            $app->render('admin-page.php', [
                'content' => $app->view->fetch('login-fragment.php')
            ]);
            return;
        }
        
        $app->redirect('/admin');

    });

    $app->get('/logout', function () use ($app) {
        try {
            \Sentry::logout();
        } catch (\Exception $e) {
            $app->flashNow('error', $e->getMessage());
        }

        $app->redirect('/admin');
    });
});

$app->group('/admin', 'Braindump\Api\Admin\Middleware\adminAuthenticate', function () use ($app, $dbFacade, $notebookFacade, $noteFacade, $userFacade) {

    $app->get('(/)', function () use ($app, $dbFacade) {

        $data = [
              'currentVersion'  => $dbFacade->getCurrentVersion(),
              'highestVersion'  => $dbFacade->getHighestVersion(),
              'migrationNeeded' => $dbFacade->isMigrationNeeded() ];

        try {
            $menuData = [
              'notebookCount'   => Notebook::count(),
              'noteCount'       => Note::count(),
              'userCount'       => User::count(),
              'user'            => \Sentry::getUser(), ];
        } catch (\Exception $e) {
            $app->flashNow('error', $e->getMessage());
        }

        $app->render('admin-page.php', [
            'menu'    => $app->view->fetch('admin-menu-fragment.php', $menuData),
            'content' => $app->view->fetch('admin-fragment.php', $data)
        ]);
    });

    $app->get('/info', function () use ($app) {
        phpinfo();
    });

    $app->get('/export', function () use ($app) {

        $groups = \ORM::for_table('groups')
            ->select_many('name', 'permissions', 'created_at', 'updated_at')
            ->find_array();

        $users = \ORM::for_table('users')->find_array();
        
        foreach ($users as &$user) {
            // Add groups to user
            $sentryUser = \Sentry::findUserById($user['id']);

            // Get the user groups
            $sentryGroups = $sentryUser->getGroups();

            foreach ($sentryGroups as $group) {
                $user['groups'][] = $group->name;
            }

            // Add notebooks to user
            $user['notebooks'] = Notebook::select_many('id', 'title', 'created', 'updated')
                ->where_equal('user_id', $user['id'])
                ->find_array();
            
            unset($user['id']);

            foreach ($user['notebooks'] as &$notebook) {
                // Add notes to notebook
                $notebook['notes'] = Note::select_many('title', 'created', 'updated', 'url', 'type', 'content')
                    ->where_equal('notebook_id', $notebook['id'])
                    ->find_array();

                unset($notebook['id']);
            }
        }

        $app->response->headers->set('Content-Disposition', 'attachment; filename=export.json');
        outputJson(['groups' => $groups, 'users' => $users], $app);
    });

    $app->post('/import', function () use ($notebookFacade, $noteFacade, $dbFacade, $app) {

        $groups = 0;
        $users = 0;
        $notebooks = 0;
        $notes = 0;

        //Check size and type of input

        // First check if JSON is posted as request body
        $input = $app->request->getBody();

        // Then check if a file upload has been made
        if (strlen($input) == 0) {
            if ($_FILES['importFile']['error'] == UPLOAD_ERR_OK               //checks for errors
                && is_uploaded_file($_FILES['importFile']['tmp_name'])) {
                //checks that file is uploaded
                $input = file_get_contents($_FILES['importFile']['tmp_name']);
            }
        }

        $data = json_decode($input);

        if (!is_object($data) || !is_array($data->groups) || !is_array($data->users)) {
            $app->flash('error', 'No (valid) data found');
            $app->redirect($app->refererringRoute);
            return;
        }

        // Process input...
        try {
            \ORM::get_db()->beginTransaction();

            // TODO: What about currently logged in users?
            // ...delete existing data...
            \ORM::for_table('note')->delete_many();
            \ORM::for_table('notebook')->delete_many();
            \ORM::for_table('throttle')->delete_many();
            \ORM::for_table('users_groups')->delete_many();
            \ORM::for_table('users')->delete_many();
            \ORM::for_table('groups')->delete_many();

            // ...create groups...
            foreach ($data->groups as $group) {
                \Sentry::createGroup((array)$group);
            }

            // ...create users....
            foreach ($data->users as $user) {
                $userArray = (array)$user;

                unset($userArray['groups']);
                unset($userArray['notebooks']);

                $sentryUser = \Sentry::createUser($userArray);

                // Bad hack: Password and activation code are already hashed
                //           Sentry will rehash them, revert this
                $userArray['id'] = $sentryUser->id;
                $sentryUser->hydratePlain($userArray);
                $sentryUser->save();
               
                // ... assign groups to suers
                if (property_exists($user, 'groups')) {
                    foreach ($user->groups as $groupName) {
                        print_r($groupName);

                        $sentryUser->addGroup(\Sentry::findGroupByName($groupName));
                    }
                }

                // ...recreate notebooks and notes for each user
                foreach ($user->notebooks as $notebookRecord) {

                    if (!Notebook::isValid($notebookRecord)) {
                        \ORM::get_db()->rollback();

                        $app->flash('error', 'Invalid data');
                        $app->redirect($app->refererringRoute);
                        return;
                    }

                    $notebook = Notebook::create();
                    $notebook->map($notebookRecord, true);

                    $notebook->user_id = $sentryUser->id;
                    $notebook->save();
                    $notebooks++;

                    foreach ($notebookRecord->notes as $noteRecord) {
                        if (!Note::isValid($noteRecord)) {
                            \ORM::get_db()->rollback();
                            $app->flash('error', 'Invalid data');
                            $app->redirect($app->refererringRoute);
                            return;
                        }

                        $note = Note::create();
                        $note->map($notebook, $noteRecord, true);
                        $note->user_id = $sentryUser->id;
                        $note->save(false);
                        $notes++;
                    }

                }
            }
            
            \ORM::get_db()->commit();
            $app->flash('success', sprintf('%d notebook(s) and %d note(s) have been imported', $notebooks, $notes));
            $app->redirect('/admin');

        } catch (\Exception $e) {
            \ORM::get_db()->rollback();
            $app->flash('error', $e->getMessage());
            $app->redirect('/admin');
        }
    });

    $app->post('/setup', function () use ($dbFacade, $app) {

        // Only perform setup if user has confirmed
        if ($app->request->params('confirm') != 'YES') {
            $app->flash('warning', 'Please confirm setup');
            $app->redirect($app->refererringRoute);
            return;
        }

        try {
            \ORM::get_db()->beginTransaction();
            $dbFacade->createDatabase();

            // Create a defauld user
            $user = \Sentry::createUser([
                'email'      => 'administrator@braindump-api.local',
                'first_name' => 'Braindump',
                'last_name'  => 'Administrator',
                'password'   => 'welcome',
                'activated'  => true,
            ]);

            $user->addGroup(\Sentry::findGroupByName('Administrators'));

            \ORM::get_db()->commit();
            $app->flash('success', 'Setup is executed');
            $app->redirect($app->refererringRoute);
            return;
        } catch (\Exception $e) {
            \ORM::get_db()->rollback();
            $app->flash('error', $e->getMessage());
            $app->redirect('/admin');
        }
        
    });

    $app->map('/migrate', function () use ($dbFacade, $app) {

        try {
            \ORM::get_db()->beginTransaction();
            $dbFacade->migrateDatabase();
            \ORM::get_db()->commit();
            $app->flash('success', sprintf('Migrated database schema to %s', $dbFacade->getCurrentVersion()));
            // get referring route does not seem to work from GET Request
            // $app->redirect($app->refererringRoute);
            $app->redirect('/admin');
            return;
        } catch (\Exception $e) {
            \ORM::get_db()->rollback();
            $app->flash('error', $e->getMessage());
            $app->redirect('/admin');
        }

    })->via('GET', 'POST');

    $app->get('/users(/)', function () use ($app) {

        $app->render(
            'admin-page.php',
            [
                'menu'    => $app->view->fetch('admin-menu-fragment.php'),
                'content' => $app->view->fetch(
                    'user-list-fragment.php',
                    [
                        'users' => \Sentry::findAllUsers()
                    ]
                )
            ]
        );
    });

    $app->get('/users/createForm', function () use ($app) {
        $app->render(
            'admin-page.php',
            [
                'menu'    => $app->view->fetch('admin-menu-fragment.php'),
                'content' => $app->view->fetch(
                    'user-form-fragment.php',
                    [
                        'groups' => \Sentry::findAllGroups()
                    ]
                )
            ]
        );
    });

    $app->get('/users/:id', function ($id) use ($app) {
        $app->render(
            'admin-page.php',
            [
                'menu'    => $app->view->fetch('admin-menu-fragment.php'),
                'content' => $app->view->fetch(
                    'user-form-fragment.php',
                    [
                        'user'   => \Sentry::findUserById($id),
                        'groups' => \Sentry::findAllGroups()
                    ]
                )
            ]
        );
    });

    $app->post('/users(/)', function () use ($app) {

        try {
            // Create the user
            $user = \Sentry::createUser([
                'email'      => htmlentities($app->request->params('email'), ENT_QUOTES, 'UTF-8'),
                'first_name' => htmlentities($app->request->params('first_name'), ENT_QUOTES, 'UTF-8'),
                'last_name'  => htmlentities($app->request->params('last_name'), ENT_QUOTES, 'UTF-8'),
                'password'   => 'welcome',
                'activated'  => true,
            ]);

            $groups = $app->request->params('groups');

            // TODO: validate that at least one group is supplied
            if (is_array($groups)) {
                foreach ($groups as $id) {
                    $user->addGroup(\Sentry::findGroupById($id));
                }
            }

            $app->flashNow('success', 'Changes have been saved');
            
            $app->render(
                'admin-page.php',
                [
                    'menu'    => $app->view->fetch('admin-menu-fragment.php'),
                    'content' => $app->view->fetch(
                        'user-list-fragment.php',
                        [
                            'users' => \Sentry::findAllUsers()
                        ]
                    )
                ]
            );

        } catch (\Exception $e) {
            $app->flashNow('error', $e->getMessage());

            // TODO: In an error situation, the Groups checkboxes are not repopulated
            $app->render(
                'admin-page.php',
                [
                    'menu'    => $app->view->fetch('admin-menu-fragment.php'),
                    'content' => $app->view->fetch(
                        'user-form-fragment.php',
                        [
                            'user' => $user,
                            'groups' => \Sentry::findAllGroups()
                        ]
                    )
                ]
            );
        }
    });

    $app->put('/users/:id', function ($id) use ($app) {

        $success = false;

        try {
            $user = \Sentry::findUserById($id);

            $user->email      = htmlentities($app->request->params('email'), ENT_QUOTES, 'UTF-8');
            $user->first_name = htmlentities($app->request->params('first_name'), ENT_QUOTES, 'UTF-8');
            $user->last_name  = htmlentities($app->request->params('last_name'), ENT_QUOTES, 'UTF-8');
            
            $success = $user->save();

            if ($success) {
                // TODO: validate that at least one group is supplied
 
                // Try to add all listed groups
                $listedGroups = $app->request->params('groups');

                if (is_array($listedGroups)) {
                    foreach ($listedGroups as $groupId) {
                        $success = $user->addGroup(\Sentry::findGroupById($groupId));
                    }
                } else {
                    $listedGroups = []; // dummy array so removing groups will succeed
                }

                // Try to remove all unlisted groups
                $allGroups = \Sentry::findAllGroups();

                foreach ($allGroups as $group) {
                    if (!in_array($group->id, $listedGroups)) {
                        $success = $user->removeGroup($group);
                    }
                }
            }

            if ($success) {
                $app->flashNow('success', 'Changes have been saved');
            }

            $app->render(
                'admin-page.php',
                [
                    'menu'    => $app->view->fetch('admin-menu-fragment.php'),
                    'content' => $app->view->fetch(
                        'user-list-fragment.php',
                        [
                            'users' => \Sentry::findAllUsers()
                        ]
                    )
                ]
            );

        } catch (\Exception $e) {
            $app->flashNow('error', $e->getMessage());

            $app->render(
                'admin-page.php',
                [
                    'menu'    => $app->view->fetch('admin-menu-fragment.php'),
                    'content' => $app->view->fetch(
                        'user-form-fragment.php',
                        [
                            'user'   => \Sentry::findUserById($id),
                            'groups' => \Sentry::findAllGroups()
                        ]
                    )
                ]
            );
        }

    });

    $app->post('/users/:id/throttle/:action(/)', function ($id, $action) use ($app) {

        try {
            $throttle = \Sentry::findThrottlerByUserId($id);

            switch ($action) {
                case 'suspend':
                    $throttle->suspend();
                    $app->flashNow('success', 'User has been suspended');
                    break;
                case 'unsuspend':
                    $throttle->unsuspend();
                    $app->flashNow('success', 'User has been unsuspended');
                    break;
                case 'ban':
                    $throttle->ban();
                    $app->flashNow('success', 'User has been banned');
                    break;
                case 'unban':
                    $throttle->unban();
                    $app->flashNow('success', 'User has been unbanned');
                    break;
                default:
                    $app->halt('500', 'Illegal action');
                    break;
            }
        } catch (\Exception $e) {
            $app->flashNow('error', $e->getMessage());
        }

        $app->render(
            'admin-page.php',
            [
                'menu'    => $app->view->fetch('admin-menu-fragment.php'),
                'content' => $app->view->fetch(
                    'user-list-fragment.php',
                    [
                        'users' => \Sentry::findAllUsers()
                    ]
                )
            ]
        );

    });

    $app->delete('/users/:id', function ($id) use ($app) {

        try {
            $user = \Sentry::findUserById($id);
            // TODO: delete notebooks and notes of user
            $user->delete();
            $app->flash('success', 'User has been deleted');
            
        } catch (\Exception $e) {
            $app->flash('error', $e->getMessage());
        }

        $app->redirect('/admin/users');
    });
    
});
