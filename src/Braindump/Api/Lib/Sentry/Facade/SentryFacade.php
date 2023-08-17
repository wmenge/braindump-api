<?php namespace Braindump\Api\Lib\Sentry\Facade;

use Braindump\Api\Model\Sentry\Paris\UserProvider as UserProvider;
use Braindump\Api\Model\Sentry\Paris\GroupProvider as GroupProvider;
use Braindump\Api\Model\Sentry\Paris\ThrottleProvider as ThrottleProvider;
use Cartalyst\Sentry\Hashing\BcryptHasher;

use Cartalyst\Sentry\Users\UserInterface as UserInterface;
use Cartalyst\Sentry\Users\ProviderInterface as UserProviderInterface;
use Cartalyst\Sentry\Groups\ProviderInterface as GroupProviderInterface;
use Cartalyst\Sentry\Throttling\ProviderInterface as ThrottleProviderInterface;
use Cartalyst\Sentry\Sessions\SessionInterface as SessionInterface;
use Cartalyst\Sentry\Sessions\NativeSession as NativeSession;
use Cartalyst\Sentry\Cookies\CookieInterface;
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
        UserProviderInterface $userProvider = null,
        GroupProviderInterface $groupProvider = null,
        ThrottleProviderInterface $throttleProvider = null,
        SessionInterface $session = null,
        CookieInterface $cookie = null,
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