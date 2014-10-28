<?php
require '../vendor/autoload.php';
require '../middleware/AttachHeaders.php';
require '../middleware/Authentication.php';
require '../lib/DatabaseFacade.php';

date_default_timezone_set('Europe/Amsterdam');

// Sentry configuration/startup
require '../app/sentry.php';

// TODO: move setup of app in separate to make inclusion in /test/bootstrap.php
$app = new \Slim\Slim(array(
    'templates.path' => '../templates',
));

// Correct headers Used by REST API
// Todo: make sure they are only used by REST Routes
$app->add(new \Braindump\Api\Middleware\AttachHeaders());

// Session used by admin routes
// Todo: make sure its only used by Admin routes
$app->add(new \Slim\Middleware\SessionCookie(array(
    'expires' => '20 minutes',
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'httponly' => false,
    'name' => 'slim_session',
    'secret' => 'BRAINDUMP_ADMIN',
    'cipher' => MCRYPT_RIJNDAEL_256,
    'cipher_mode' => MCRYPT_MODE_CBC
)));

$app->refererringRoute = function () use ($app) {
    if (strpos($app->environment['HTTP_REFERER'], $app->environment['HTTP_ORIGIN']) !== false) {
        return str_replace($app->environment['HTTP_ORIGIN'], '', $app->environment['HTTP_REFERER']);
    } else {
        return "/";
    }
};

$app->braindumpConfig = (require '../config/braindump-config.php');

ORM::configure($app->braindumpConfig[database_config]);

function outputJson($data, $app)
{
    // JSON_NUMERIC_CHECK is needed as PDO will return strings
    // as default (even if DB schema defines numeric types).
    // http://stackoverflow.com/questions/11128823/how-to-properly-format-pdo-results-numeric-results-returned-as-string
    // todo: replace with proper rendering engine?
    $app->response->headers->set('Content-Type', 'application/json');
    $app->response()->body(json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK));
}

// Angular JS will preflight Cross domain POST and PUT request
// with JSON content
// http://stackoverflow.com/questions/12111936/angularjs-performs-an-options-http-request-for-a-cross-origin-resource
// By returning a CORS header on the pre-flight Request everybody is happy
$app->options('/:wildcard+', function () {
    header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS, DELETE');
});

$app->get('/', function () use ($app) {
    $app->redirect('/admin');
});

require_once '../routes/admin.php';
require_once '../routes/note.php';
require_once '../routes/notebook.php';

$app->run();
