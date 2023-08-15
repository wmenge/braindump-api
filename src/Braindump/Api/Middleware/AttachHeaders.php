<?php namespace Braindump\Api\Middleware;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class AttachHeaders
{
	private $container;

    public function __construct($container) {
        $this->container = $container;
    }

    function __invoke(Request $req,  Response $res, callable $next) {

        return $next($req, $res)
            ->withHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization')
            ->withHeader('Access-Control-Allow-Origin', $this->container->get('settings')['braindump']['client_cors_domain'])
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

    }
}