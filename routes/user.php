<?php

$app->group('/api', function () {

    $this->get('/user[/]', '\Braindump\Api\Controller\User\UserController:getUser');
    $this->put('/user[/]', '\Braindump\Api\Controller\User\UserController:putUser');

})->add('Braindump\Api\Middleware\Authentication:apiAuthorize')->add('Braindump\Api\Middleware\Authentication:apiAuthenticate');