<?php

namespace Braindump\Api\Middleware;

class AttachHeaders extends \Slim\Middleware
{
    public function call()
    {
        $this->app->response->headers->set('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept');
        $this->app->response->headers->set('Access-Control-Allow-Origin', $this->app->braindumpConfig['client_cors_domain']);
        $this->app->response->headers->set('Content-Type', 'application/json');

        // Run inner middleware and application
        $this->next->call();
    }
}
