<?php namespace Braindump\Api\Controller\User;

use Braindump\Api\Model\UserConfiguration as UserConfiguration;
use Braindump\Api\Model\Notebook as Notebook;
use Braindump\Api\Lib\Sentry\Facade\SentryFacade as Sentry;

class UserConfigurationController extends \Braindump\Api\Controller\BaseController {
   
    private $configurationFacade;

    public function __construct(\Psr\Container\ContainerInterface $ci) {
        $this->configurationFacade = new \Braindump\Api\Model\UserConfigurationFacade();
        UserConfiguration::setNotebookFacade(new \Braindump\Api\Model\NotebookFacade());
        parent::__construct($ci);
    }

    public function getConfiguration($request, $response) {

        $configuration = $this->configurationFacade->getConfiguration();

        if ($configuration == null) {
            $configuration = UserConfiguration::create();
            $configuration->email_to_notebook = null;
        }
        
        return $this->outputJson($configuration->as_array('email', 'email_to_notebook'), $response);
    }

        
    public function modifyConfiguration($request, $response) {

        $input = json_decode($request->getBody());

        if (!UserConfiguration::isValid($input)) {
            return $response->withStatus(400);
            //$app->halt(400, 'Invalid input');
        }

        $configuration = $this->configurationFacade->getConfiguration(true);

        if ($configuration == null) {
            $configuration = UserConfiguration::create();
        }

        $configuration->map($input);
        // Move to map?
        $configuration->user_id = Sentry::getUser()->getId();
        $configuration->save();

        return $this->outputJson($configuration->as_array('email_to_notebook'), $response);
    }

}