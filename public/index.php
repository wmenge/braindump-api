<?php
require '../vendor/autoload.php';
require '../middleware/AttachHeaders.php';
require '../lib/DatabaseHelper.php';

$app = new \Slim\Slim();
$app->add(new \Braindump\Api\Middleware\AttachHeaders());

$app->idiormConfig = (require '../config/idiorm-config.php');
$app->braindumpConfig = (require '../config/braindump-config.php');

ORM::configure($app->idiormConfig);

function outputJson($data)
{
    // JSON_NUMERIC_CHECK is needed as PDO will return strings
    // as default (even if DB schema defines numeric types).
    // http://stackoverflow.com/questions/11128823/how-to-properly-format-pdo-results-numeric-results-returned-as-string
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);
}

// Angular JS will preflight Cross domain POST and PUT request
// with JSON content
// http://stackoverflow.com/questions/12111936/angularjs-performs-an-options-http-request-for-a-cross-origin-resource
// By returning a CORS header on the pre-flight Request everybody is happy
$app->options('/:wildcard+', function () {
    header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS, DELETE');
});

$app->post('/setup', function () use ($app) {
    $dbHelper = new \Braindump\Api\DatabaseHelper();
    $dbHelper->createDatabase(ORM::get_db(), $app->braindumpConfig['databases_setup_scripts']);
});

$app->get('/info', function () {
    phpinfo();
});

require_once '../routes/note.php';
require_once '../routes/notebook.php';

$app->run();
