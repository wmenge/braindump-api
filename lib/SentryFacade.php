<?php

namespace Braindump\Api\Lib\Sentry\Facade;

use Cartalyst\Sentry\Users\Eloquent\Provider as UserProvider;
use Cartalyst\Sentry\Hashing\BcryptHasher;

class SentryFacade extends \Cartalyst\Sentry\Facades\Native\Sentry
{
    public static function instance()
    {
        return parent::instance(new UserProvider(new BcryptHasher));
    }
}