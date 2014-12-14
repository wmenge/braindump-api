<?php
namespace Braindump\Api\Lib\Sentry\Facade;

// Macbook: 
// ========
// No sentry:               0.023 seconds, mem: 2158912
// Sentry with eloquent:    0.159 seconds, mem: 5618008
// Sentry with Paris:       0.054 seconds, mem: 3091608

// CH3SNAS: 
// ========
// No sentry:               0.667 seconds, mem: 1459564
// Sentry with eloquent:    1.839 seconds, mem: 2863656
// Sentry with Paris:       

require '../model/Sentry/Paris/UserProvider.php';
require '../model/Sentry/Paris/GroupProvider.php';
require '../model/Sentry/Paris/ThrottleProvider.php';

use Cartalyst\Sentry\Users\Paris\UserProvider as UserProvider;
use Cartalyst\Sentry\Groups\Paris\GroupProvider as GroupProvider;
use Cartalyst\Sentry\Throttling\Paris\ThrottleProvider as ThrottleProvider;
use Cartalyst\Sentry\Hashing\BcryptHasher;

use Cartalyst\Sentry\Users\UserInterface as UserInterface;
use Cartalyst\Sentry\Users\ProviderInterface as UserProviderInterface;
use Cartalyst\Sentry\Groups\ProviderInterface as GroupProviderInterface;
use Cartalyst\Sentry\Throttling\ProviderInterface as ThrottleProviderInterface;
use Cartalyst\Sentry\Sessions\NativeSession as NativeSession;
use Cartalyst\Sentry\Cookies\NativeCookie as NativeCookie;

class SentryFacade extends \Cartalyst\Sentry\Facades\Native\Sentry
{
    
    /**
     * Creates a Sentry instance.
     *
     * @param  \Cartalyst\Sentry\Users\ProviderInterface $userProvider
     * @param  \Cartalyst\Sentry\Groups\ProviderInterface $groupProvider
     * @param  \Cartalyst\Sentry\Throttling\ProviderInterface $throttleProvider
     * @param  \Cartalyst\Sentry\Sessions\SessionInterface $session
     * @param  \Cartalyst\Sentry\Cookies\CookieInterface $cookie
     * @param  string $ipAddress
     * @return \Cartalyst\Sentry\Sentry
     */
    public static function createSentry(
        UsersProviderInterface $userProvider = null,
        GroupProviderInterface $groupProvider = null,
        ThrottleProviderInterface $throttleProvider = null,
        \SessionInterface $session = null,
        \CookieInterface $cookie = null,
        $ipAddress = null
    ) {
        $userProvider = $userProvider ?: new UserProvider(new BcryptHasher);

        return new \Cartalyst\Sentry\Sentry(
            $userProvider,
            $groupProvider    ?: new GroupProvider,
            $throttleProvider ?: new ThrottleProvider($userProvider),
            $session          ?: new NativeSession,
            $cookie           ?: new NativeCookie,
            $ipAddress        ?: static::guessIpAddress()
        );
    }
}

class_alias('Braindump\Api\Lib\Sentry\Facade\SentryFacade', 'Sentry');
