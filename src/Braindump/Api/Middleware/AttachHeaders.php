<?php namespace Braindump\Api\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Container\ContainerInterface;
use Slim\Psr7\Response;

class AttachHeaders
{
	private $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        //echo "AttachHeaders";
        //echo "header middleware (SHOULD RUN FIRST)";
        return $handler->handle($request)
            ->withHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization')
            ->withHeader('Access-Control-Allow-Origin', $this->ci->get('settings')['client_cors_domain'])
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');

    }
}