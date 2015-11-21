<?php
namespace Braindump\Api;

require_once(__DIR__ . '/../model/UserConfigurationFacade.php');
require_once(__DIR__ . '/../model/NotebookFacade.php');

use Braindump\Api\Model\UserConfiguration as UserConfiguration;
use Braindump\Api\Model\Notebook as Notebook;

$configurationFacade = new \Braindump\Api\Model\UserConfigurationFacade();
UserConfiguration::setNotebookFacade(new \Braindump\Api\Model\NotebookFacade());

$app->group('/api', 'Braindump\Api\Admin\Middleware\apiAuthenticate', function () use ($app, $configurationFacade) {

    $app->get('/configuration(/)', function () use ($app, $configurationFacade) {

        $configuration = $configurationFacade->getConfiguration();

        if ($configuration == null) {
            $configuration = UserConfiguration::create();
            $configuration->email_to_notebook = null;
        }
        
        outputJson($configuration->as_array('email_to_notebook'), $app);
    });

    $app->map('/configuration(/)', function () use ($app, $configurationFacade) {
        
        $input = json_decode($app->request->getBody());

        if (!UserConfiguration::isValid($input)) {
            $app->halt(400, 'Invalid input');
        }

        $configuration = $configurationFacade->getConfiguration(true);

        if ($configuration == null) {
            $configuration = UserConfiguration::create();
        }

        $configuration->map($input);
        // Move to map?
        $configuration->user_id = \Sentry::getUser()->getId();
        $configuration->save();

        outputJson($configuration->as_array(), $app);
    })->via('POST', 'PUT');

});
