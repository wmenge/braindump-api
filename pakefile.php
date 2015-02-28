<?php
include './vendor/autoload.php';
require './lib/SentryFacade.php';
require_once(__DIR__ . '/lib/DatabaseFacade.php');

//$notebookFacade = new \Braindump\Api\Model\NotebookFacade($dbFacade);
//$noteFacade = new \Braindump\Api\Model\NoteFacade($dbFacade);
//$userFacade = new \Braindump\Api\Model\UserFacade($dbFacade);

pake_desc('Setup Braindump API');
pake_task('setup');
function run_setup()
{
    // init app and db
    $app = new \Slim\Slim(array(
        'templates.path' => './templates',
    ));

    $app->braindumpConfig = (require './config/braindump-config.php');

    ORM::configure($app->braindumpConfig['database_config']);

    $dbFacade = new \Braindump\Api\Lib\DatabaseFacade($app, \ORM::get_db());

    // Todo: first create an export of the db
    \ORM::get_db()->beginTransaction();
    $dbFacade->createDatabase();

    // Create a defauld user
    $user = \Sentry::createUser([
        'email'      => 'administrator@braindump-api.local',
        'first_name' => 'Braindump',
        'last_name'  => 'Administrator',
        'password'   => 'welcome',
        'activated'  => true,
    ]);

    $user->addGroup(\Sentry::findGroupByName('Administrators'));

    \ORM::get_db()->commit();
    echo 'Setup task has been performed' . PHP_EOL;
}
