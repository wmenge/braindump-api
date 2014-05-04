<?php 
require '../vendor/autoload.php';

$app = new \Slim\Slim();

// Move to slim configuration place
ORM::configure('sqlite:../data/braindump.sqlite');

function outputJson($data) {
	// Todo: Correct header on all requests using middleware
	header("Content-Type: application/json");
	header("Access-Control-Allow-Origin: *");
	echo json_encode($data, JSON_PRETTY_PRINT);
}

// Angular JS will preflight Cross domain POST and PUT request
// with JSON content
// http://stackoverflow.com/questions/12111936/angularjs-performs-an-options-http-request-for-a-cross-origin-resource
// By returning a CORS header on the pre-flight Request everybody is happy
$app->options('/:wildcard+', function() {
	// TODO: Restrict to some configurable domain
	//header("Content-Type: application/json");
	//header("Accept: application/json");
	header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
	header("Access-Control-Allow-Origin: *");
	header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS, DELETE');
            
});

require_once('../routes/notebook.php');
require_once('../routes/note.php');

$app->run();