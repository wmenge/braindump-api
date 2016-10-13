<?php namespace Braindump\Api;

require_once(__DIR__ . '/../controllers/UserConfigurationController.php');

$app->group('/api', function () {

    $this->get('/configuration[/]', '\Braindump\Api\Controller\User\UserConfigurationController:getConfiguration');
    $this->map(['POST', 'PUT'], '/configuration[/]', '\Braindump\Api\Controller\User\UserConfigurationController:modifyConfiguration');

})->add('Braindump\Api\Admin\Middleware\apiAuthorize')->add('Braindump\Api\Admin\Middleware\apiAuthenticate');
