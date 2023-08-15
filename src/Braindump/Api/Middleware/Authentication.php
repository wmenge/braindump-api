<?php namespace Braindump\Api\Middleware;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
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

    public static function adminAuthorize(Request $req,  Response $res, callable $next) {

        if (!self::routeIsAllowed(Sentry::getUser(), $req->getUri()->getPath())) {
            return $res->withStatus(403);
        }

        return $next($req, $res);
    }

    /***
     * Route middleware implementing form based authentication. To be used 
     * by the Admin web interface
     */
    public static function adminAuthenticate(Request $req,  Response $res, callable $next)
    {
        // Check if a user is logged in
        if (!Sentry::check()) {

            // Check if http authentication credentials have been passed
            // (command line client scenario)e
            if ($req->getHeaderLine('PHP_AUTH_USER') && $req->getHeaderLine('PHP_AUTH_PW')) {
                Sentry::authenticate(
                    [ 'login'    => $req->getHeaderLine('PHP_AUTH_USER'),
                    'password' => $req->getHeaderLine('PHP_AUTH_PW') ]
                );
            } else {
                return $res->withStatus(302)->withHeader('Location', '/login');
            }
        }

        return $next($req, $res);
    }


    /***
     * Route middleware implementing basic HTTP authorization. To be used
     * by API routes
     */
    public static function apiAuthorize(Request $req,  Response $res, callable $next) {

        if (!self::routeIsAllowed(Sentry::getUser(), $req->getUri()->getPath())) {
            return $res->withStatus(403, 'No permision');
        }

        return $next($req, $res);
    }

    /***
     * Route middleware implementing basic HTTP authentication. To be used
     * by API routes
     */
    public static function apiAuthenticate(Request $req,  Response $res, callable $next)
    {
        if (Sentry::check()) { 
            return $next($req, $res); 
        }

        return $res->withStatus(401);
    }

}
