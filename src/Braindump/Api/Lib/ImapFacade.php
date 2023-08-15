<?php namespace Braindump\Api\Lib;

class ImapFacade
{
    private $config;

    private function __construct($config, $server)
    {
        $this->config = $config;
        $this->server = $server;
    }

    public static function createFacade($config, $server = null)
    {
        if ($server == null) {
            $server = new \Fetch\Server($config['server'], $config['port']);
            $server->setAuthentication($config['user'], $config['password']);
            $server->setMailbox($config['sourceFolder']);
        }
        
        return new ImapFacade($config, $server);
    }

    public function getMessages()
    {
        if ($this->server->numMessages() > $this->config['messageLimit']) {
            throw new \Exception(
                sprintf(
                    'Error: %d (more than %d) messages found in %s, stop processing...',
                    $this->server->numMessages(),
                    $this->config['messageLimit'],
                    $this->config['sourceFolder']
                )
            );
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
                'content' => $message->getMessageBody(true), // HTML will be sanitized in $note->map()
                'created' => $message->getDate(),
                'updated' => $message->getDate()
            ];

            $notebook = $user->configuration()->find_one()->emailToNotebook()->find_one();

            $note = \Braindump\Api\Model\Note::create();
            $note->map($notebook, $dataObject, true);
            $note->user_id = $user->id;

            $note->save(false);
        }

        $message->moveToMailBox($this->config['processedFolder']);
    }
}
