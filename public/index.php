<?php 
require '../vendor/autoload.php';
require '../middleware/attach_headers.php';

$app = new \Slim\Slim();
$app->add(new AttachHeaders());

$app->idiormConfig = (require '../config/idiorm-config.php');
$app->braindumpConfig = (require '../config/braindump-config.php');

ORM::configure($app->idiormConfig);

function outputJson($data) {
	echo json_encode($data, JSON_PRETTY_PRINT);
}

// Angular JS will preflight Cross domain POST and PUT request
// with JSON content
// http://stackoverflow.com/questions/12111936/angularjs-performs-an-options-http-request-for-a-cross-origin-resource
// By returning a CORS header on the pre-flight Request everybody is happy
$app->options('/:wildcard+', function() {
	header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS, DELETE');            
});
});

require_once('../routes/note.php');
require_once('../routes/notebook.php');

$app->run();