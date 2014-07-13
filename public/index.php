<?php 
require '../vendor/autoload.php';
require '../middleware/attach_headers.php';

$app = new \Slim\Slim();
$app->add(new AttachHeaders());

$app->idiormConfig = (require '../config/idiorm-config.php');
$app->braindumpConfig = (require '../config/braindump-config.php');

ORM::configure($app->idiormConfig);

function createDatabase($app) {
	
	$db = ORM::get_db();

	// Fetch initial SQL script and subsequent migration scripts
	$scripts = $app->braindumpConfig['databases_setup_scripts'];

	// For initial setup, just run all scripts
	// TODO: Migration scenarios
	foreach ($scripts as $version => $script) {
		echo sprintf('Execute script for version %s<br />', $version);
		$sql = file_get_contents($script);
    	$db->exec($sql);	
	}

	echo 'Setup performed';
}

function outputJson($data) {
	// JSON_NUMERIC_CHECK is needed as PDO will return strings
	// as default (even if DB schema defines numeric types).
	// http://stackoverflow.com/questions/11128823/how-to-properly-format-pdo-results-numeric-results-returned-as-string
	echo json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK);
}

// Angular JS will preflight Cross domain POST and PUT request
// with JSON content
// http://stackoverflow.com/questions/12111936/angularjs-performs-an-options-http-request-for-a-cross-origin-resource
// By returning a CORS header on the pre-flight Request everybody is happy
$app->options('/:wildcard+', function() {
	header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS, DELETE');            
});

$app->get('/setup', function() use ($app) {
	createDatabase($app);
});

require_once('../routes/note.php');
require_once('../routes/notebook.php');

$app->run();