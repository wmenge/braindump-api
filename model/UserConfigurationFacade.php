<?php namespace Braindump\Api\Model;

require_once(__DIR__ . '/../lib/SentryFacade.php');
require_once(__DIR__ . '/UserConfigurationFacade.php');

class UserConfigurationFacade
{
    public function getConfiguration()
    {
        return \Sentry::getUser()->configuration()->find_one();
    }
}
