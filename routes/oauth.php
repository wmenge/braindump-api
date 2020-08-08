<?php namespace Braindump\Api\Oauth;

require_once(__DIR__ . '/../controllers/Oauth2Controller.php');
        
$app->group('/oauth2', function () {
    $this->get('/{provider}/login', '\Braindump\Api\Controller\Oauth2Controller:login');
    $this->get('/{provider}/callback', '\Braindump\Api\Controller\Oauth2Controller:callback');
    $this->get('/logout', '\Braindump\Api\Controller\Oauth2Controller:logout');
});