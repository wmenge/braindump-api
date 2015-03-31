<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/ImapFacade.php';

//class_alias('Braindump\Api\Lib\Sentry\Facade\SentryFacade', 'Sentry');

date_default_timezone_set('Europe/Amsterdam');

$worker = new \Kohkimakimoto\Worker\Worker();

// ... job definitions
$worker->job('process-mail', ['cron_time' => '* * * * *', 'command' => function() use ($worker) {

    try {
        // Setup DB connection
        $braindumpConfig = (require __DIR__ . '/../config/braindump-config.php');
        ORM::configure($braindumpConfig['database_config']);

        $imapFacade = \Braindump\Api\Lib\ImapFacade::createFacade($braindumpConfig['imap_config']);

        $messages = $imapFacade->getMessages();

        $worker->output->writeln('<comment>'.sizeof($messages).'</comment> Messages found');

        $imapFacade->processMessages($messages);

    } catch (Exception $ex) {
        $worker->output->writeln("<fg=red>".$ex->getMessage()."</fg=red>");
    }

}]);

$worker->start();
