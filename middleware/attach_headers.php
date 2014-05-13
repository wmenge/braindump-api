<?php
class AttachHeaders extends \Slim\Middleware
{
    public function call()
    {
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
        header('Access-Control-Allow-Origin: ' . $this->app->braindumpConfig['client_cors_domain']);
        header('Content-Type: application/json');

        // Run inner middleware and application
        $this->next->call();
    }
}