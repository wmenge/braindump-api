<?php
include './vendor/autoload.php';

use Braindump\Api\Lib\HTMLToMarkdown\HtmlConverterFacade;
use Braindump\Api\Lib\Sentry\Facade\SentryFacade as Sentry;

use AFM\Rsync\Rsync;
use Braindump\Api\model\Sentry\Paris\Group;
use Braindump\Api\Model\Note as Note;
use League\HTMLToMarkdown\HtmlConverter;


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

desc('Convert HTML to Markdown');
task('convert-to-markdown', function() {

    echo 'Converting notes from HTML to Markdown...' . PHP_EOL;

    $converterFacade = new HtmlConverterFacade();

    // Setup DB connection
    $braindumpConfig = (require __DIR__ . '/config/braindump-config.php');
    ORM::configure($braindumpConfig['database_config']);

    $notes = Note::select_many('id', 'title', 'created', 'updated', 'url', 'type', 'content')
                    ->find_result_set();

    foreach ($notes as &$note) {
        $note->content = $converterFacade->convert($note->content);
        $note->type = 'Markdown';
        $note->save();
    }

    echo 'Notes have been converted' . PHP_EOL;
});