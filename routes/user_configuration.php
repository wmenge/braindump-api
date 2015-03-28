<?php
namespace Braindump\Api;

require_once(__DIR__ . '/../model/UserConfigurationFacade.php');

use Braindump\Api\Model\UserConfiguration as UserConfiguration;

$configurationFacade = new \Braindump\Api\Model\UserConfigurationFacade();

$app->group('/api', 'Braindump\Api\Admin\Middleware\apiAuthenticate', function () use ($app, $configurationFacade) {

    $app->get('/configuration(/)', function () use ($app, $configurationFacade) {

        $configuration = $configurationFacade->getConfiguration();

        if ($configuration == null) {
            return $app->notFound();
        }

        outputJson($configuration->as_array(), $app);
    });

    $app->map('/configuration(/)', function ($id) use ($app, $configurationFacade) {
        
        $input = json_decode($app->request->getBody());

        if (!UserConfiguration::isValid($input)) {
            $app->halt(400, 'Invalid input');
        }

        $configuration = $configurationFacade->getConfiguration(true);

        if ($configuration == null) {
            $configuration = UserConfiguration::create();
        }

        $configuration->map($input);
        $configuration->user_id = \Sentry::getUser()->getId();
        $configuration->save();

        outputJson($configuration->as_array(), $app);
    })->via('POST', 'PUT');

});
