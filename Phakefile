<?php
include './vendor/autoload.php';

use Braindump\Api\Lib\Sentry\Facade\SentryFacade as Sentry;
use AFM\Rsync\Rsync;
use Braindump\Api\model\Sentry\Paris\Group;

desc('Setup Braindump API');
task('setup', function() {
        
    // Setup DB connection
    $braindumpConfig = (require __DIR__ . '/config/braindump-config.php');
    ORM::configure($braindumpConfig['database_config']);

    $dbFacade = new \Braindump\Api\Lib\DatabaseFacade(
        \ORM::get_db(),
        (require( __DIR__ . '/migrations/migration-config.php')));
    
    // Todo: first create an export of the db
    \ORM::get_db()->beginTransaction();
    $dbFacade->createDatabase();

    // Create a default user
    $user = Sentry::createUser([
        'login'      => $braindumpConfig['initial_admin_user'],
        'name' => 'Braindump Administrator',
        'password'   => 'welcome',
        'activated'  => true,
    ]);

    $user->addGroup(Sentry::findGroupByName('Administrators'));

    \ORM::get_db()->commit();
    echo 'Setup task has been performed' . PHP_EOL;
});

desc('Migrate Braindump Database');
task('migrate', function() {
        
    // Setup DB connection
    $braindumpConfig = (require __DIR__ . '/config/braindump-config.php');
    ORM::configure($braindumpConfig['database_config']);

    $dbFacade = new \Braindump\Api\Lib\DatabaseFacade(
        \ORM::get_db(),
        (require( __DIR__ . '/migrations/migration-config.php')));

    if ($dbFacade->isMigrationNeeded()) {
        echo 'Database schema is not up to date and should be updated' . PHP_EOL;
        echo sprintf('Current version: %d, available version: %d' . PHP_EOL, $dbFacade->getCurrentVersion(), $dbFacade->getHighestVersion());
    } else {
        echo 'Database schema is up to date, no action needed' . PHP_EOL;
        echo sprintf('Current version: %d' . PHP_EOL, $dbFacade->getCurrentVersion());
        return;
    };

    try {
        echo 'Updating database schema...' . PHP_EOL;
        \ORM::get_db()->beginTransaction();
        $dbFacade->migrateDatabase();
        \ORM::get_db()->commit();
    
        echo sprintf('Migrated database schema to %s' . PHP_EOL, $dbFacade->getCurrentVersion());

    } catch (\Exception $e) {    
        \ORM::get_db()->rollback();
        echo $e->getMessage() . PHP_EOL;
    }
});

desc('Reset Administrator user');
task('reset_admin', function() {

    // Setup DB connection
    $braindumpConfig = (require __DIR__ . '/config/braindump-config.php');
    ORM::configure($braindumpConfig['database_config']);

    $user = Sentry::findUserByLogin($braindumpConfig['initial_admin_user']);

    $user->password = 'welcome';
    $user->save();

    $throttle = Sentry::findThrottlerByUserId($user->id);
    $throttle->unsuspend();
    $throttle->unban();
    echo 'Administrator has been reset' . PHP_EOL;

});

/*desc('Sync Braindump API');
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
});*/