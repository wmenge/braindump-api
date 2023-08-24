<?php
require '../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Braindump\Api\Middleware\AttachHeaders;

date_default_timezone_set('Europe/Amsterdam');
session_start();

error_reporting(E_ALL);
error_reporting(-1);
ini_set('error_reporting', E_ALL);

// Setup DI
$builder = new DI\ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../config/container.php');
$container = $builder->build();
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add Routing Middleware
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

ORM::configure($container->get('settings')['database_config']);

$app->get('/', function ($request, $response) use ($app) {
	//$response->getBody()->write('my content');
	//return $response;
	//$container = $app->getContainer();
	//$redirectToClient = $container->get('settings')['redirect_to_client'];

	//if ($redirectToClient) {
	//    return $response->withStatus(302)->withHeader('Location', '/client');
	//} else {
    	return $response->withStatus(302)->withHeader('Location', '/admin');
	//}
});

require_once '../routes/admin.php';
require_once '../routes/note.php';
require_once '../routes/notebook.php';
require_once '../routes/user.php';
require_once '../routes/user_configuration.php';
require_once '../routes/file.php';
require_once '../routes/authentication.php';

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// Correct headers Used by REST API
// TODO: make sure they are only used by REST Routes
$app->add(Braindump\Api\Middleware\AttachHeaders::class);

$app->run();