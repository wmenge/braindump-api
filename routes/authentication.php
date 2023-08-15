<?php

// TODO: When logged in, redirect to /
$app->group('', function () {
    $this->get('/login', '\Braindump\Api\Controller\Html\CredentialsLoginController:getLogin');
    $this->post('/login', '\Braindump\Api\Controller\Html\CredentialsLoginController:postLogin');
    $this->get('/logout', '\Braindump\Api\Controller\Html\CredentialsLoginController:getLogout');
});

$app->group('/oauth2', function () {
    $this->get('/{provider}/login', '\Braindump\Api\Controller\Oauth2Controller:login');
    $this->get('/{provider}/callback', '\Braindump\Api\Controller\Oauth2Controller:callback');
});