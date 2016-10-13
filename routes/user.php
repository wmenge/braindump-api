<?php namespace Braindump\Api;

require_once(__DIR__ . '/../controllers/UserController.php');

$app->group('/api', function () {

    $this->get('/user[/]', '\Braindump\Api\Controller\User\UserController:getUser');

})->add('Braindump\Api\Admin\Middleware\apiAuthorize')->add('Braindump\Api\Admin\Middleware\apiAuthenticate');