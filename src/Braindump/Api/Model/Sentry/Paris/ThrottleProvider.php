<?php namespace Braindump\Api\Model\Sentry\Paris;

use Cartalyst\Sentry\Throttling\ThrottleInterface;
use Cartalyst\Sentry\Throttling\ProviderInterface;
use Cartalyst\Sentry\Users\ProviderInterface as UserProviderInterface;
use Cartalyst\Sentry\Users\UserInterface;

class ThrottleProvider implements ProviderInterface {

    /**
     * The user provider used for finding users
     * to attach throttles to.
     *
     * @var \Cartalyst\Sentry\Users\UserInterface
     */
    protected $userProvider;

    /**
     * Throttling status.
     *
     * @var bool
     */
    protected $enabled = true;

    /**
     * Creates a new throttle provider.
     *
     * @param \Cartalyst\Sentry\Users\ProviderInterface $userProvider
     * @param  string $model
     * @return void
     */
    public function __construct(UserProviderInterface $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    /**
     * Finds a throttler by the given Model.
     *
     * @param  \Cartalyst\Sentry\Users\UserInterface $user
     * @param  string  $ipAddress
     * @return \Cartalyst\Sentry\Throttling\ThrottleInterface
     */
    public function findByUser(UserInterface $user, $ipAddress = null)
    {
        $query = \Model::factory(Throttle::class)
                    ->where_equal('user_id', ($userId = $user->getId()));
        //          ->find_one();

        if ($ipAddress)
        {
            $query = $query->where_raw('(`ip_address` = ? OR `ip_address` = ?)', array($ipAddress, null));
            /*$query->where(function($query) use ($ipAddress) {
                $query->where('ip_address', '=', $ipAddress);
                $query->orWhere('ip_address', '=', NULL);
            });*/
        }

        $throttle = $query->find_one();

        if (!$throttle)
        {
            $throttle = $this->createModel();
            $throttle->user_id = $userId;
            if ($ipAddress) $throttle->ip_address = $ipAddress;
            $throttle->save();
        }

        return $throttle;
    }
    /**
     * Finds a throttler by the given user ID.
     *
     * @param  mixed   $id
     * @param  string  $ipAddress
     * @return \Cartalyst\Sentry\Throttling\ThrottleInterface
     */
    public function findByUserId($id, $ipAddress = null)
    {
        return $this->findByUser($this->userProvider->findById($id),$ipAddress);
    }

    /**
     * Finds a throttling interface by the given user login.
     *
     * @param  string  $login
     * @param  string  $ipAddress
     * @return \Cartalyst\Sentry\Throttling\ThrottleInterface
     */
    public function findByUserLogin($login, $ipAddress = null)
    {
        return $this->findByUser($this->userProvider->findByLogin($login),$ipAddress);
    }

    /**
     * Enable throttling.
     *
     * @return void
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * Disable throttling.
     *
     * @return void
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Check if throttling is enabled.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        return \Model::factory(Throttle::class)->create();
    }

    /**
     * Sets a new model class name to be used at
     * runtime.
     *
     * @param  string  $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

}
