<?php

$app->group('/api', function () {

    $this->get('/configuration[/]', '\Braindump\Api\Controller\User\UserConfigurationController:getConfiguration');
    $this->map(['POST', 'PUT'], '/configuration[/]', '\Braindump\Api\Controller\User\UserConfigurationController:modifyConfiguration');

})->add('Braindump\Api\Middleware\Authentication:apiAuthorize')->add('Braindump\Api\Middleware\Authentication:apiAuthenticate');
