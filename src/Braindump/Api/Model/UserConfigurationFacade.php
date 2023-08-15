<?php namespace Braindump\Api\Model;

use Braindump\Api\Lib\Sentry\Facade\SentryFacade as Sentry;

class UserConfigurationFacade
{
    public function getConfiguration()
    {
        return Sentry::getUser()->configuration()->find_one();
    }
}
