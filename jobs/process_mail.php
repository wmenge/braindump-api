<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/ImapFacade.php';

date_default_timezone_set('Europe/Amsterdam');

echo 'Connect to imap server and process messages...' . PHP_EOL;

try {
    // Setup DB connection
    $braindumpConfig = (require __DIR__ . '/../config/braindump-config.php');
    ORM::configure($braindumpConfig['database_config']);

    $imapFacade = \Braindump\Api\Lib\ImapFacade::createFacade($braindumpConfig['imap_config']);

    // Fetch and process messages
    $messages = $imapFacade->getMessages();
    echo sizeof($messages) . ' messages found' . PHP_EOL;

    $imapFacade->processMessages($messages);

} catch (Exception $ex) {
    echo $ex->getMessage() . PHP_EOL;
}