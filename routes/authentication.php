<?php

use Slim\Routing\RouteCollectorProxy;

// TODO: When logged in, redirect to /
$app->group('', function (RouteCollectorProxy $group) {
    $group->get('/login', '\Braindump\Api\Controller\Html\CredentialsLoginController:getLogin');
    $group->post('/login', '\Braindump\Api\Controller\Html\CredentialsLoginController:postLogin');
    $group->get('/logout', '\Braindump\Api\Controller\Html\CredentialsLoginController:getLogout');
});

$app->group('/oauth2', function (RouteCollectorProxy $group) {
    $group->get('/{provider}/login', '\Braindump\Api\Controller\Oauth2Controller:login');
    $group->get('/{provider}/callback', '\Braindump\Api\Controller\Oauth2Controller:callback');
});