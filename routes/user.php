<?php
namespace Braindump\Api;

require_once(__DIR__ . '/../lib/SentryFacade.php');

$app->group('/api', 'Braindump\Api\Admin\Middleware\apiAuthenticate', function () use ($app) {

    $app->get('/user(/)', function () use ($app) {

        $user = \Sentry::getUser();

        $userArray = [
            'id'         => $user->id,
            'email'      => $user->email,
            'activated'  => $user->activated,
            'last_login' => $user->last_login,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name
        ];

        outputJson($userArray, $app);
    });
});
