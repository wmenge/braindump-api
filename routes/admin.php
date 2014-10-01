<?php
namespace Braindump\Api\Admin;

require_once(__DIR__ . '/../lib/DatabaseHelper.php');
require_once(__DIR__ . '/../model/NotebookHelper.php');
require_once(__DIR__ . '/../model/NoteHelper.php');
require_once(__DIR__ . '/../model/UserHelper.php');
$dbHelper = new \Braindump\Api\Lib\DatabaseHelper($app, \ORM::get_db());
$notebookHelper = new \Braindump\Api\Model\NotebookHelper($dbHelper);
$noteHelper = new \Braindump\Api\Model\NoteHelper($dbHelper);
$userHelper = new \Braindump\Api\Model\UserHelper($dbHelper);


$app->group('/admin', function () use ($app, $dbHelper, $notebookHelper, $noteHelper, $userHelper) {

    $app->get('/login', function () use ($app, $dbHelper, $userHelper) {
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

$app->group('/admin', 'Braindump\Api\Admin\Middleware\adminAuthenticate', function () use ($app, $dbHelper, $notebookHelper, $noteHelper, $userHelper) {

    $app->get('(/)', function () use ($app, $dbHelper, $userHelper) {

        $data = [
              'currentVersion'  => $dbHelper->getCurrentVersion(),
              'highestVersion'  => $dbHelper->getHighestVersion(),
              'migrationNeeded' => $dbHelper->isMigrationNeeded() ];

        try {
            $menuData = [
              'notebookCount'   => \ORM::for_table('notebook')->count(),
              'noteCount'       => \ORM::for_table('note')->count(),
              'userCount'       => \ORM::for_table('users')->count(),
              'user'            => \Sentry::getUser(), ];
        } catch (\Exception $e) {
            $app->flashNow('error', $e->getMessage());
        }

        $app->render('admin-page.php', [
            'menu'    => $app->view->fetch('admin-menu-fragment.php', $menuData),
            'content' => $app->view->fetch('admin-fragment.php', $data)
        ]);
    });

    $app->get('/export', function () use ($app) {

        $notebooks = \ORM::for_table('notebook')->find_array();

        foreach ($notebooks as &$notebook) {
            $notebook['notes'] = \ORM::for_table('note')
            ->select_many('id', 'title', 'created', 'updated', 'url', 'type', 'content', 'user_id')
            ->where_equal('notebook_id', $notebook['id'])->find_array();
        }
        
        $app->response->headers->set('Content-Disposition', 'attachment; filename=export.json');
        outputJson($notebooks, $app);
    });

    $app->post('/import', function () use ($notebookHelper, $noteHelper, $dbHelper, $app) {

        $notebooks = 0;
        $notes = 0;

        //Check size and type of input

        // First check if JSON is posted as request body
        $input = $app->request->getBody();

        // Then check if a file upload has been made
        if (strlen($input) == 0) {
            if ($_FILES['importFile']['error'] == UPLOAD_ERR_OK               //checks for errors
                && is_uploaded_file($_FILES['importFile']['tmp_name'])) { //checks that file is uploaded
                $input = file_get_contents($_FILES['importFile']['tmp_name']);
            }
        }

        $notebookRecords = json_decode($input);

        if (!is_array($notebookRecords)) {
            $app->flash('error', 'No (valid) data found');
            $app->redirect($app->refererringRoute);
            return;
        }
        
        try {
            \ORM::get_db()->beginTransaction();

            \ORM::for_table('note')->delete_many();
            \ORM::for_table('notebook')->delete_many();
            
            foreach ($notebookRecords as $notebookRecord) {

                if (!$notebookHelper->isValid($notebookRecord)) {
                    \ORM::get_db()->rollback();

                    $app->flash('error', 'Invalid data');
                    $app->redirect($app->refererringRoute);
                    return;
                }

                $notebook = \ORM::for_table('notebook')->create();
                $notebookHelper->map($notebook, $notebookRecord, true);

                // Todo: Check errors after db operations
                $notebook->save();
                $notebooks++;

                foreach ($notebookRecord->notes as $noteRecord) {
                    if (!$noteHelper->isValid($noteRecord)) {
                        \ORM::get_db()->rollback();
                        $app->flash('error', 'Invalid data');
                        $app->redirect($app->refererringRoute);
                        return;
                    }

                    $note = \ORM::for_table('note')->create();
                    $noteHelper->map($note, $notebook, $noteRecord, true);
                    $note->save();
                    $notes++;
                }
            }
            
            \ORM::get_db()->commit();
            $app->flash('success', sprintf('%d notebook(s) and %d note(s) have been imported', $notebooks, $notes));
            $app->redirect('/admin');

        } catch (\Exception $e) {
            //\ORM::get_db()->rollback();
            $app->flash('error', $e->getMessage());
            $app->redirect('/admin');
        }
    });

    $app->post('/setup', function () use ($dbHelper, $app) {

        // Only perform setup if user has confirmed
        if ($app->request->params('confirm') != 'YES') {
            $app->flash('warning', 'Please confirm setup');
            $app->redirect($app->refererringRoute);
            return;
        }

        try {
            \ORM::get_db()->beginTransaction();
            $dbHelper->createDatabase();
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

    $app->map('/migrate', function () use ($dbHelper, $app) {

        try {
            \ORM::get_db()->beginTransaction();
            $dbHelper->migrateDatabase();
            \ORM::get_db()->commit();
            $app->flash('success', sprintf('Migrated database schema to %s', $dbHelper->getCurrentVersion()));
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

        //var $user == null;

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

            foreach ($groups as $id) {
                $user->addGroup(\Sentry::findGroupById($id));
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

            // Todo: In an error situation, the Groups checkboxes are not repopulated
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
                
                // Try to add all listed groups
                $listedGroups = $app->request->params('groups');
                
                foreach ($listedGroups as $groupId) {
                    $success = $user->addGroup(\Sentry::findGroupById($groupId));
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
            // Todo: delete notebooks and notes of user
            $user->delete();
            $app->flash('success', 'User has been deleted');
            
        } catch (\Exception $e) {
            $app->flash('error', $e->getMessage());
        }

        $app->redirect('/admin/users');
    });
    
});
