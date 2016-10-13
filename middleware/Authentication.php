<?php namespace Braindump\Api\Admin\Middleware;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

// Refactor: Add as separate authorization middleware step
function routeIsAllowed($user, $route)
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

function adminAuthorize(Request $req,  Response $res, callable $next) {

    if (!routeIsAllowed(\Sentry::getUser(), $req->getUri()->getPath())) {
        return $res->withStatus(403)->withHeader('Location', '/admin');
    }

    return $next($req, $res);
}

/***
 * Route middleware implementing form based authentication. To be used 
 * by the Admin web interface
 */
//function adminAuthenticate()
function adminAuthenticate(Request $req,  Response $res, callable $next)
{
    // Check if a user is logged in
    if (!\Sentry::check()) {

        // Check if http authentication credentials have been passed
        // (command line client scenario)e
        if ($req->getHeader('PHP_AUTH_USER') && $req->getHeader('PHP_AUTH_PW')) {
            \Sentry::authenticate(
                [ 'email'    => $req->getHeader('PHP_AUTH_USER'),
                  'password' => $req->getHeader('PHP_AUTH_PW') ]
            );
        } else {
            return $res->withStatus(401)->withHeader('Location', '/admin/login');
        }
    }

    return $next($req, $res);
}


/***
 * Route middleware implementing basic HTTP authorization. To be used
 * by API routes
 */
function apiAuthorize(Request $req,  Response $res, callable $next) {

    if (!routeIsAllowed(\Sentry::getUser(), $req->getUri()->getPath())) {
        return $res->withStatus(403, 'No permision');
    }

    return $next($req, $res);
};

/***
 * Route middleware implementing basic HTTP authentication. To be used
 * by API routes
 */
function apiAuthenticate(Request $req,  Response $res, callable $next)
{
    if (\Sentry::check()) { 
        return $next($req, $res); 
    }
    
    try {
        \Sentry::authenticate(
            [ 'email'    => $req->getHeaderLine('PHP_AUTH_USER'),
              'password' => $req->getHeaderLine('PHP_AUTH_PW') ]
        );
   
    } catch (\Exception $e) {
        return $res->withHeader('WWW-Authenticate', 'Basic realm="Braindump"')->withStatus(401, $e->getMessage());
    }

    return $next($req, $res);
}
