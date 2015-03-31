<?php namespace Braindump\Api\Lib;

require_once(__DIR__ . '/../model/Sentry/Paris/User.php');
require_once(__DIR__ . '/../model/Notebook.php');
require_once(__DIR__ . '/../model/Note.php');

class ImapFacade
{
    protected static $default_config = [
        'server'          => 'imap.yourserver.com',
        'port'            => 993,
        'messageLimit'    => 10,
        'user'            => 'your_imap_user',
        'password'        => 'your_imap_password',
        'sourceFolder'    => 'Inbox',
        'processedFolder' => 'Processed'
    ];

    private $config;

    private function __construct($config, $server)
    {
        $this->config = $config;
        $this->server = $server;
    }

    public static function createFacade($config, $server = null)
    {
        return new ImapFacade(
            $config,
            $server ?: new \Fetch\Server($config['server'], $config['port'])
        );
    }

    public function getMessages()
    {
        $this->server->setAuthentication($this->config['user'], $this->config['password']);
        $this->server->setMailbox($this->config['sourceFolder']);

        if ($this->server->numMessages() > $this->config['messageLimit']) {
            throw new Exception("Too many messages in ".$server->getMailBox());
        }

        return $this->server->getMessages();
    }

    public function processMessages($messages)
    {
        foreach ($messages as $message) {
            $this->processMessage($message);
        }
    }

    private function processMessage($message)
    {
        $sender = (object)$message->getAddresses('sender');
           
        $user = \Cartalyst\Sentry\Users\Paris\User::where('email', $sender->address)->find_one();
        
        if (!empty($user)) {

            $dataObject = (object)[
                'title' => $message->getSubject(),
                'type' => 'HTML',
                'content' => $message->getMessageBody(true) // HTML will be sanitized in $note->map()
            ];

            // TODO: Store email folder on user
            $notebook = $user->configuration()->find_one()->emailToNotebook()->find_one();

            $note = \Braindump\Api\Model\Note::create();
            $note->map($notebook, $dataObject);
            $note->user_id = $user->id;
            $note->save();
        }

        $message->moveToMailBox($this->config['processedFolder']);
    }
}
