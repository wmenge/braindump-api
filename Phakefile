<?php
include './vendor/autoload.php';
require_once(__DIR__ . '/lib/SentryFacade.php');
require_once(__DIR__ . '/lib/DatabaseFacade.php');

use AFM\Rsync\Rsync;

desc('Setup Braindump API');
task('setup', function() {
        // init app and db
        $app = new \Slim\Slim(array(
            'templates.path' => __DIR__ . '/templates',
        ));

        $app->braindumpConfig = (require __DIR__ . '/config/braindump-config.php');

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
    });

desc('Sync Braindump API');
task('sync', function() {
    //rsync -avz -e 'ssh' --exclude-from 'rsync_exclude.txt' '/www/braindump/' 'wmenge@10.0.0.2:/mnt/sdb2/www/vhosts/braindump'
    // pakeRSync::sync_to_server(__DIR__, '10.0.0.2', '/mnt/sdb2/www/vhosts/braindump-api', 'wmenge');

    $origin = __DIR__ . '/';
    $target = "/www/braindump-test/";
//echo $origin;

    $rsync = new Rsync;
    //$rsync->setDryRun(true);
    $rsync->setVerbose(true);
    $rsync->setDeleteFromTarget(true);
    $rsync->setExcludeFrom('rsync_exclude.txt');
    //$rsync->setOptionalParameters(['Wilco'=>'test']);
    echo $rsync->getCommand($origin, $target) . PHP_EOL;
    $rsync->sync($origin, $target);
});