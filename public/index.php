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

$app->refererringRoute = function () use ($app) {
    if (strpos($app->environment['HTTP_REFERER'], $app->environment['HTTP_ORIGIN']) !== false) {
        return str_replace($app->environment['HTTP_ORIGIN'], '', $app->environment['HTTP_REFERER']);
    } else {
        return "/";
    }
};

ORM::configure($container->get('settings')['braindump']['database_config']);

$app->get('/', function ($request, $response) {
	$redirectToClient = $container->get('settings')['braindump']['redirect_to_client'];

	if ($redirectToClient) {
	    return $response->withStatus(302)->withHeader('Location', '/client');
	} else {
    	return $response->withStatus(302)->withHeader('Location', '/admin');
	}

});

require_once '../routes/admin.php';
require_once '../routes/note.php';
require_once '../routes/notebook.php';
require_once '../routes/user.php';
require_once '../routes/user_configuration.php';
require_once '../routes/file.php';

$app->run();