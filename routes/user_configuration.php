<?php
use Slim\Routing\RouteCollectorProxy;

$app->group('/api', function (RouteCollectorProxy $group)
{
    $group->get('/configuration', '\Braindump\Api\Controller\User\UserConfigurationController:getConfiguration');
    $group->map(['POST', 'PUT'], '/configuration[/]', '\Braindump\Api\Controller\User\UserConfigurationController:modifyConfiguration');
})->add('Braindump\Api\Middleware\Authentication:apiAuthorize')->add('Braindump\Api\Middleware\Authentication:apiAuthenticate');
