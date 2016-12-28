<?php namespace Braindump\Api\Controller\Admin;

require_once __DIR__ . '/AdminBaseController.php';

class AdminUserController extends \Braindump\Api\Controller\AdminBaseController {

    public function getUsers($request, $response) {

        return $this->renderer->render($response, 'admin-page.php', [
            'menu'    => $this->renderer->fetch('admin-menu-fragment.php'),
            'content' => $this->renderer->fetch('user-list-fragment.php', [ 'users' => \Sentry::findAllUsers() ])
        ]);

    }

    public function getCreateForm($request, $response) {

        return $this->renderer->render($response, 'admin-page.php', [
            'menu'    => $this->renderer->fetch('admin-menu-fragment.php'),
            'content' => $this->renderer->fetch('user-form-fragment.php', [ 'groups' => \Sentry::findAllGroups() ])
        ]);

    }

    public function getUser($request, $response, $args) {

        return $this->renderer->render(
            $response, 'admin-page.php',
            [
                'menu'    => $this->renderer->fetch('admin-menu-fragment.php'),
                'content' => $this->renderer->fetch(
                    'user-form-fragment.php',
                    [
                        'user'   => \Sentry::findUserById($args['id']),
                        'groups' => \Sentry::findAllGroups()
                    ]
                )
            ]
        );

    }

    public function postUser($request, $response) {

        $user = null;
        $error = false;

        // validate password (for now, if none supplied, use default password)
        $password = htmlentities($request->getParam('password'));
        $password_confirm = htmlentities($request->getParam('password_confirm'));

        if (empty($password)) {
            $password = 'welcome';
            $flash = [ 'warning' => 'No password entered, default password given' ];
        } elseif ($password != $password_confirm) {
            $error = true;
            $flash = [ 'error' => 'Passwords do not match' ];
        }
        
        if (!$error) {

            try {

                // Create the user
                $user = \Sentry::createUser([
                    'email'      => htmlentities($request->getParam('email'), ENT_QUOTES, 'UTF-8'),
                    'first_name' => htmlentities($request->getParam('first_name'), ENT_QUOTES, 'UTF-8'),
                    'last_name'  => htmlentities($request->getParam('last_name'), ENT_QUOTES, 'UTF-8'),
                    'password'   => $password,
                    'activated'  => true,
                ]);

                $groups = $request->getParam('groups');

                // TODO: validate that at least one group is supplied
                if (is_array($groups)) {
                    foreach ($groups as $id) {
                        $user->addGroup(\Sentry::findGroupById($id));
                    }
                }

                $this->flash->addMessage('success', 'Changes have been saved');
                return $response->withStatus(302)->withHeader('Location', '/admin/users');

            } catch (\Exception $e) {
                $flash = [ 'error' => $e->getMessage() ];
            }

        }
                
        // TODO: In an error situation, the Groups checkboxes are not repopulated
        return $this->renderer->render(
            $response, 'admin-page.php',
            [
                'flash'   => $flash,
                'menu'    => $this->renderer->fetch('admin-menu-fragment.php'),
                'content' => $this->renderer->fetch(
                    'user-form-fragment.php',
                    [
                        'user' => $user,
                        'groups' => \Sentry::findAllGroups()
                    ]
                )
            ]
        );
        
    }

    public function putUser($request, $response, $args) {

        $error = false;
        $success = false;
        $flash = null;

        try {

            $user = \Sentry::findUserById($args['id']);

            $user->email      = htmlentities($request->getParam('email'), ENT_QUOTES, 'UTF-8');
            $user->first_name = htmlentities($request->getParam('first_name'), ENT_QUOTES, 'UTF-8');
            $user->last_name  = htmlentities($request->getParam('last_name'), ENT_QUOTES, 'UTF-8');

            // validate password
            $password = htmlentities($request->getParam('password'));
            $password_confirm = htmlentities($request->getParam('password_confirm'));

            if (!empty($password)) {
                if ($password != $password_confirm) {
                    $error = true;
                    $flash = [ 'error' => 'Passwords do not match' ];
                } else {
                    $user->password = $password;
                } 
            }

            if (!$error) $success = $user->save();

            if ($success) {
                // TODO: validate that at least one group is supplied
 
                // Try to add all listed groups
                $listedGroups = $request->getParam('groups');

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
                $this->flash->addMessage('success', 'Changes have been saved');
                return $response->withStatus(302)->withHeader('Location', '/admin/users');
            }

        } catch (\Exception $e) {
            $flash = [ 'error' => $e->getMessage() ];
        }

        // If we reach this point, something bad must have happened
        return $this->renderer->render(
            $response, 'admin-page.php',
            [
                'flash'   => $flash,
                'menu'    => $this->renderer->fetch('admin-menu-fragment.php'),
                'content' => $this->renderer->fetch(
                'user-form-fragment.php',
                [
                    'user'   => \Sentry::findUserById($args['id']),
                    'groups' => \Sentry::findAllGroups()
                ])
            ]
        );
    }

    public function postThrottle($request, $response, $args) {

        $flash = [];

        try {
            $throttle = \Sentry::findThrottlerByUserId($args['id']);

            switch ($args['action']) {
                case 'suspend':
                    $throttle->suspend();
                    $flash = [ 'success' => 'User has been suspended' ];
                    break;
                case 'unsuspend':
                    $throttle->unsuspend();
                    $flash = [ 'success' => 'User has been unsuspended' ];
                    break;
                case 'ban':
                    $throttle->ban();
                    $flash = [ 'success' => 'User has been banned' ];
                    break;
                case 'unban':
                    $throttle->unban();
                    $flash = [ 'success' => 'User has been unbanned' ];
                    break;
                default:
//                    $app->halt('500', 'Illegal action');
                    break;
            }
        } catch (\Exception $e) {
            $flash = [ 'error' => $e->getMessage() ];
        }

        return $this->renderer->render(
            $response, 'admin-page.php',
            [
                'flash'   => $flash,
                'menu'    => $this->renderer->fetch('admin-menu-fragment.php'),
                'content' => $this->renderer->fetch(
                    'user-list-fragment.php',
                    [
                        'users' => \Sentry::findAllUsers()
                    ]
                )
            ]
        );
    }

    public function deleteUser($request, $response, $args) {

        try {
            $user = \Sentry::findUserById($args['id']);
            // TODO: delete notebooks and notes of user
            $user->delete();
            $this->flash->addMessage('success', 'User has been deleted');
            
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
        }

        return $response->withStatus(302)->withHeader('Location', '/admin/users');
    }

}
