<?php

require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../lib/SentryFacade.php';
require_once __DIR__ . '/../lib/DatabaseFacade.php';
require_once(__DIR__ . '/../model/Notebook.php');
require_once(__DIR__ . '/../model/Note.php');

date_default_timezone_set('Europe/Amsterdam');

$worker = new \Kohkimakimoto\Worker\Worker();

// ... job definitions
$worker->job('process-mail', ['cron_time' => '* * * * *', 'command' => function() use ($worker) {

    // TODO: Refactor into some IMAPFacade

    try {
        // Setup DB connection
        $braindumpConfig = (require __DIR__ . '/../config/braindump-config.php');
        ORM::configure($braindumpConfig['database_config']);

        // Setup Imap connection

        $config = (object)$braindumpConfig['imap_config'];
        $server = new \Fetch\Server($config->server, $config->port);
        $server->setAuthentication($config->user, $config->password);

        $server->setMailbox($config->sourceFolder);

        $messageCount = $server->numMessages();

        if ($messageCount > $config->messageLimit) {
            throw new Exception("Too many messages in ".$server->getMailBox());
        }

        $worker->output->writeln('<comment>'.$messageCount.'</comment> Messages found in '.$server->getMailBox());

        $messages = $server->getMessages();

        foreach ($messages as $message) {
            $sender = (object)$message->getAddresses('sender');
           
            $user = \Cartalyst\Sentry\Users\Paris\User::where('email', $sender->address)->find_one();
            
            if (!$user) {
                $worker->output->writeln('Message from <comment>'.$sender->address.'</comment> ignored.');
            } else {
                $worker->output->writeln('<info>Message found from user <comment>'.$user->email.'</comment>.</info>');

                // TODO: Store email folder on user
                $notebook = \Braindump\Api\Model\Notebook::find_one(2);

                $note = \Braindump\Api\Model\Note::create();

                $dataObject = (object)[
                    'title' => $message->getSubject(),
                    'type' => 'HTML',
                    'content' => $message->getMessageBody()
                ];

                $note->map($notebook, $dataObject);
                $note->user_id = $user->id;
                $note->save();
            }

            $message->moveToMailBox($config->processedFolder);
        }

    } catch (Exception $ex) {
        $worker->output->writeln("<fg=red>".$ex->getMessage()."</fg=red>");
    }

}]);

$worker->start();
