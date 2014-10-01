<?php

namespace Braindump\Api\Admin\Middleware;

function routeIsAllowed($user, $route)
{
    $permissions = $user->getMergedPermissions();

    // Check if route is allowed by any of the permissions
    // (permissions contain regexes specifying valid paths)
    foreach ($permissions as $permission => $value) {
        if ($value == 1 && preg_match($permission, $route) == 1) {
            return true;
        }
    }
    return false;
}

/***
 * Route middleware implementing form based authentication. To be used 
 * by the Admin web interface
 */
function adminAuthenticate()
{
    $app = \Slim\Slim::getInstance();
    
    // Check if a user is logged in
    if (!\Sentry::check()) {
        $app->redirect('/admin/login');
    }

    if (!routeIsAllowed(\Sentry::getUser(), $app->environment['PATH_INFO'])) {
        $app->flash('error', 'No permission');
        $app->redirect('/admin');
    }
}

/***
 * Route middleware implementing basic HTTP authentication. To be used
 * by API routes
 */
function apiAuthenticate()
{
    $app = \Slim\Slim::getInstance();

    try {
        \Sentry::authenticate(
            [ 'email'    => $app->request()->headers('PHP_AUTH_USER'),
              'password' => $app->request()->headers('PHP_AUTH_PW') ]
        );

        if (!routeIsAllowed(\Sentry::getUser(), $app->environment['PATH_INFO'])) {
            $app->log->info('no permission');
            $app->halt('403', 'No permision');
        }

    } catch (\Exception $e) {
        $app->response()->header('WWW-Authenticate', 'Basic realm="Braindump"');
        $app->halt('401', $e->getMessage());
    }
}
