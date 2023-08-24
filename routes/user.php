<?php

use Slim\Routing\RouteCollectorProxy;

$app->group('/api', function (RouteCollectorProxy $group) {

    $group->get('/user', '\Braindump\Api\Controller\User\UserController:getUser');
    $group->put('/user', '\Braindump\Api\Controller\User\UserController:putUser');

})->add('Braindump\Api\Middleware\Authentication:apiAuthorize')->add('Braindump\Api\Middleware\Authentication:apiAuthenticate');