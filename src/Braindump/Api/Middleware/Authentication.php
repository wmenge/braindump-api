<?php namespace Braindump\Api\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Braindump\Api\Lib\Sentry\Facade\SentryFacade as Sentry;

class Authentication {

    // Refactor: Add as separate authorization middleware step
    public static function routeIsAllowed($user, $route)
    {
        $permissions = $user->getMergedPermissions();

        // Check if route is allowed by any of the permissions
        // (permissions contain regexes specifying valid paths)
        foreach ($permissions as $permission => $value) {
            if ($value == 1 && preg_match($permission, $route) == 1) {
                return true;
            }
        }
        return false;
    }

    public static function adminAuthorize(Request $request, RequestHandler $handler): Response {//, callable $next) {
        if (!Sentry::getUser() || !self::routeIsAllowed(Sentry::getUser(), $request->getUri()->getPath())) {
            $response = new \Slim\Psr7\Response();
            return $response->withStatus(403);
        }

        return $handler->handle($request);
    }

    /***
     * Route middleware implementing form based authentication. To be used 
     * by the Admin web interface
     */
    public static function adminAuthenticate(Request $request, RequestHandler $handler): Response
    {
        // Check if a user is logged in
        if (!Sentry::check()) {

            // Check if http authentication credentials have been passed
            // (command line client scenario)e
            if ($request->getHeaderLine('PHP_AUTH_USER') && $request->getHeaderLine('PHP_AUTH_PW')) {
                Sentry::authenticate(
                    [ 'login'    => $request->getHeaderLine('PHP_AUTH_USER'),
                    'password' => $request->getHeaderLine('PHP_AUTH_PW') ]
                );
            } else {
                return (new \Slim\Psr7\Response())->withStatus(302)->withHeader('Location', '/login');
            }
        }
        return $handler->handle($request);
    }

    /***
     * Route middleware implementing basic HTTP authorization. To be used
     * by API routes
     */
    public static function apiAuthorize(Request $request, RequestHandler $handler): Response {

        if (!self::routeIsAllowed(Sentry::getUser(), $request->getUri()->getPath())) {
            return (new \Slim\Psr7\Response())->withStatus(403, 'No permision');
        }

        return $handler->handle($request);
    }

    /***
     * Route middleware implementing basic HTTP authentication. To be used
     * by API routes
     */
    public static function apiAuthenticate(Request $request, RequestHandler $handler): Response
    { 
        if (Sentry::check()) { 
            return $handler->handle($request);
        }

        return (new \Slim\Psr7\Response())->withStatus(401);
    }

}
