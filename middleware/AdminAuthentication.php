<?php

namespace Braindump\Api\Admin\Middleware;

function adminAuthenticate()
{

    $app = \Slim\Slim::getInstance();
    
    // Check if a user is logged in
    if (!\Sentry::check()) {
        $app->redirect('/admin/login');
    }

    // Check if the current route is allowed (permissions contain regexes specifying valid paths)
    $route = $app->environment['PATH_INFO'];
    $permissions = \Sentry::getUser()->getMergedPermissions();

    foreach ($permissions as $permission => $value) {
        if ($value == 1 && preg_match($permission, $route) == 1) {
            return;
        }
    }

    // If current route matches no permission, redirect to admin page and show message
    $app->flash('error', 'No permission');
    $app->redirect('/admin');
}
