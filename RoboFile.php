<?php
use Braindump\Api\Lib\HTMLToMarkdown\HtmlConverterFacade;
use Braindump\Api\Lib\Sentry\Facade\SentryFacade as Sentry;

use AFM\Rsync\Rsync;
use Braindump\Api\model\Sentry\Paris\Group;
use Braindump\Api\Model\Note as Note;
use League\HTMLToMarkdown\HtmlConverter;

date_default_timezone_set('Europe/Amsterdam');
session_start();

error_reporting(E_ALL);
error_reporting(-1);

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see https://robo.li/
 */
class RoboFile extends \Robo\Tasks
{
    private $dbFacade;
    private $braindumpConfig;

    public function __construct()
    {
        // TODO: Proper dependency management
        $this->braindumpConfig = (require __DIR__ . '/config/braindump-config.php');
        ORM::configure($this->braindumpConfig['database_config']);
    
        $this->dbFacade = new \Braindump\Api\Lib\DatabaseFacade(
            \ORM::get_db(),
            (require( __DIR__ . '/migrations/migration-config.php')));
    }

    public function setup() {            
        // Todo: first create an export of the db
        $this->dbFacade->createDatabase();

        // Create a default user
        $user = Sentry::createUser([
            'login'      => $this->braindumpConfig['initial_admin_user'],
            'name' => 'Braindump Administrator',
            'password'   => 'welcome',
            'activated'  => true,
        ]);

        $user->addGroup(Sentry::findGroupByName('Administrators'));

        echo 'Setup task has been performed' . PHP_EOL;
    }

    public function migrate() {  
        
        if ($this->dbFacade->isMigrationNeeded()) {
            echo 'Database schema is not up to date and should be updated' . PHP_EOL;
            echo sprintf('Current version: %d, available version: %d' . PHP_EOL, $this->dbFacade->getCurrentVersion(), $this->dbFacade->getHighestVersion());
        } else {
            echo 'Database schema is up to date, no action needed' . PHP_EOL;
            echo sprintf('Current version: %d' . PHP_EOL, $this->dbFacade->getCurrentVersion());
            return;
        };
    
        try {
            echo 'Updating database schema...' . PHP_EOL;
            \ORM::get_db()->beginTransaction();
            $this->dbFacade->migrateDatabase();
            \ORM::get_db()->commit();
        
            echo sprintf('Migrated database schema to %s' . PHP_EOL, $this->dbFacade->getCurrentVersion());
    
        } catch (\Exception $e) {    
            \ORM::get_db()->rollback();
            echo $e->getMessage() . PHP_EOL;
        }
    }

    public function resetAdmin() { 
        $user = Sentry::findUserByLogin($braindumpConfig['initial_admin_user']);

        $user->password = 'welcome';
        $user->save();

        $throttle = Sentry::findThrottlerByUserId($user->id);
        $throttle->unsuspend();
        $throttle->unban();
        echo 'Administrator has been reset' . PHP_EOL;
    }

    public function convertToMarkDown() { 
        echo 'Converting notes from HTML to Markdown...' . PHP_EOL;

        $converterFacade = new HtmlConverterFacade();

        $notes = Note::select_many('id', 'title', 'created', 'updated', 'url', 'type', 'content')
                        ->find_result_set();

        foreach ($notes as &$note) {
            $note->content = $converterFacade->convert($note->content);
            $note->type = 'Markdown';
            $note->save();
        }

        echo 'Notes have been converted' . PHP_EOL;
    }
}