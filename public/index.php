<?php
require '../vendor/autoload.php';

require '../lib/SentryFacade.php';
class_alias('Braindump\Api\Lib\Sentry\Facade\SentryFacade', 'Sentry');

require '../middleware/AttachHeaders.php';
require '../middleware/Authentication.php';

date_default_timezone_set('Europe/Amsterdam');
session_start();

// TODO: move setup of app in separate to make inclusion in /test/bootstrap.php
$app = new \Slim\App([ 'settings' => [
        'displayErrorDetails' => true,
        'braindump' => (require '../config/braindump-config.php')
    ] ]);

$container = $app->getContainer();

// only needed for admin routes, move to middleware?
$container['flash'] = function () { return new \Slim\Flash\Messages(); };
$variables = ['flash' => $container['flash']->getMessages() ];
$container['renderer'] = new \Slim\Views\PhpRenderer(__DIR__ . '/../templates/', $variables);

// Correct headers Used by REST API
// TODO: make sure they are only used by REST Routes
$app->add('\Braindump\Api\Middleware\AttachHeaders');

// Session used by admin routes
// TODO: make sure its only used by Admin routes
// built in cookie management replaced by https://github.com/dflydev/dflydev-fig-cookies
/*$app->add(new \Slim\Middleware\SessionCookie([
    'expires' => '20 minutes',
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'httponly' => false,
    'name' => 'slim_session',
    'secret' => 'BRAINDUMP_ADMIN',
    'cipher' => MCRYPT_RIJNDAEL_256,
    'cipher_mode' => MCRYPT_MODE_CBC
]));*/

$app->refererringRoute = function () use ($app) {
    if (strpos($app->environment['HTTP_REFERER'], $app->environment['HTTP_ORIGIN']) !== false) {
        return str_replace($app->environment['HTTP_ORIGIN'], '', $app->environment['HTTP_REFERER']);
    } else {
        return "/";
    }
};

ORM::configure($container->get('settings')['braindump']['database_config']);

// replace with slims default helper (except for export)
function outputJson($data, $response)
{
    // JSON_NUMERIC_CHECK is needed as PDO will return strings
    // as default (even if DB schema defines numeric types).
    // http://stackoverflow.com/questions/11128823/how-to-properly-format-pdo-results-numeric-results-returned-as-string
    // TODO: replace with proper rendering engine?
    $response = $response->withHeader('Content-Type', 'application/json');
    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK));
    return $response;
}

// Angular JS will preflight Cross domain POST and PUT request
// with JSON content
// http://stackoverflow.com/questions/12111936/angularjs-performs-an-options-http-request-for-a-cross-origin-resource
// By returning a CORS header on the pre-flight Request everybody is happy
//$app->options('/:wildcard+', function () {
//    header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS, DELETE');
//});

$app->get('/', function ($request, $response) {
    return $response->withStatus(302)->withHeader('Location', '/admin');
});

require_once '../routes/admin.php';
require_once '../routes/note.php';
require_once '../routes/notebook.php';
require_once '../routes/user.php';
require_once '../routes/user_configuration.php';
require_once '../routes/file.php';

$app->run();
