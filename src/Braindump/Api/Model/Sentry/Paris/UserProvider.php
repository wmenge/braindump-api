<?php namespace Braindump\Api\Model\Sentry\Paris;

use Cartalyst\Sentry\Hashing\HasherInterface as HasherInterface;
use Cartalyst\Sentry\Groups\GroupInterface;
use Cartalyst\Sentry\Users\ProviderInterface as UserProviderInterface;
use Cartalyst\Sentry\Users\UserInterface;
use Cartalyst\Sentry\Users\UserNotActivatedException;
use Cartalyst\Sentry\Users\UserNotFoundException;
use Cartalyst\Sentry\Users\WrongPasswordException;

class UserProvider implements UserProviderInterface {

    /**
     * The hasher for the model.
     *
     * @var \Cartalyst\Sentry\Hashing\HasherInterface
     */
    protected $hasher;

    /**
     * Create a new Paris User provider.
     *
     * @param  \Cartalyst\Sentry\Hashing\HasherInterface  $hasher
     * @param  string  $model
     * @return void
     */
    public function __construct(HasherInterface $hasher)
    {
        $this->hasher = $hasher;
        $this->setupHasherWithModel();
    }

    /**
     * Finds a user by the given user ID.
     *
     * @param  mixed  $id
     * @return \Cartalyst\Sentry\Users\UserInterface
     * @throws \Cartalyst\Sentry\Users\UserNotFoundException
     */
    public function findById($id)
    {
        $user = \Model::factory(User::class)
                    ->where_equal('id', $id)
                    ->find_one();

        if (!$user) {
            throw new UserNotFoundException("A user could not be found with ID [$id].");
        }

        return $user;
    }

    /**
     * Finds a user by the login value.
     *
     * @param  string  $login
     * @return \Cartalyst\Sentry\Users\UserInterface
     * @throws \Cartalyst\Sentry\Users\UserNotFoundException
     */
    public function findByLogin($login)
    {
        $model = $this->createModel();

        $user = \Model::factory(User::class)
                    ->where_equal($model->getLoginName(), $login)
                    ->find_one();

        if (!$user) {
            throw new UserNotFoundException("A user could not be found with a login value of [$login].");
        }

        return $user;

        /*$model = $this->createModel();

        if ( ! $user = $model->newQuery()->where($model->getLoginName(), '=', $login)->first())
        {
            throw new UserNotFoundException("A user could not be found with a login value of [$login].");
        }

        return $user;*/
    }

    /**
     * Finds a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Cartalyst\Sentry\Users\UserInterface
     * @throws \Cartalyst\Sentry\Users\UserNotFoundException
     */
    public function findByCredentials(array $credentials)
    {
        $model     = $this->createModel();
        $loginName = $model->getLoginName();

        if (!array_key_exists($loginName, $credentials)) {
            throw new \InvalidArgumentException("Login attribute [$loginName] was not provided.");
        }

        $passwordName = $model->getPasswordName();

        $query = \Model::factory(User::class);

        //$user = \Model::factory(User::class)
       //             ->where_equal($passwordName, $login)
        //            ->find_one();

//        $query              = $model->newQuery();

        //$query = //Model::factory(User::class);
        $hashableAttributes = $model->getHashableAttributes();
        $hashedCredentials  = array();

        // build query from given credentials
        foreach ($credentials as $credential => $value) {
            // Remove hashed attributes to check later as we need to check these
            // values after we retrieved them because of salts
            if (in_array($credential, $hashableAttributes)) {
                $hashedCredentials = array_merge($hashedCredentials, array($credential => $value));
            } else {
                $query = $query->where_equal($credential, $value);
            }
        }

        $user = $query->find_one();

        if (!$user) {
            throw new UserNotFoundException("A user was not found with the given credentials.");
        }

        // Now check the hashed credentials match ours
        foreach ($hashedCredentials as $credential => $value) {
            if (!$this->hasher->checkHash($value, $user->{$credential})) {
                $message = "A user was found to match all plain text credentials however hashed credential [$credential] did not match.";

                if ($credential == $passwordName) {
                    throw new WrongPasswordException($message);
                }

                throw new UserNotFoundException($message);
            } else if ($credential == $passwordName) {
                if (method_exists($this->hasher, 'needsRehashed') &&
                    $this->hasher->needsRehashed($user->{$credential})) {
                    // The algorithm used to create the hash is outdated and insecure.
                    // Rehash the password and save.
                    $user->{$credential} = $value;
                    $user->save();
                }
            }
        }

        return $user;
    }

    /**
     * Finds a user by the given activation code.
     *
     * @param  string  $code
     * @return \Cartalyst\Sentry\Users\UserInterface
     * @throws \Cartalyst\Sentry\Users\UserNotFoundException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function findByActivationCode($code)
    {
        if (!$code) {
            throw new \InvalidArgumentException("No activation code passed.");
        }

        $result = \Model::factory(User::class)
                    ->where_equal('activation_code', $code)
                    ->find_result_set();

        //$model = $this->createModel();
        //$result = $model->newQuery()->where('activation_code', '=', $code)->get();

        if (($count = $result->count()) > 1) {
            throw new \RuntimeException("Found [$count] users with the same activation code.");
        }

        if ($count == 0 || !$user = $result[0]) {
            throw new UserNotFoundException("A user was not found with the given activation code.");
        }

        return $user;
    }

    /**
     * Finds a user by the given reset password code.
     *
     * @param  string  $code
     * @return \Cartalyst\Sentry\Users\UserInterface
     * @throws RuntimeException
     * @throws \Cartalyst\Sentry\Users\UserNotFoundException
     */
    public function findByResetPasswordCode($code)
    {
        //$model = $this->createModel();
        //$result = $model->newQuery()->where('reset_password_code', '=', $code)->get();

        $result = \Model::factory(User::class)
                    ->where_equal('reset_password_code', $code)
                    ->find_result_set();

        if (($count = $result->count()) > 1) {
            throw new \RuntimeException("Found [$count] users with the same reset password code.");
        }

        if ($count == 0 || !$user = $result[0]) {
            throw new UserNotFoundException("A user was not found with the given reset password code.");
        }

        return $user;
    }

    /**
     * Returns an array containing all users.
     *
     * @return array
     */
    public function findAll()
    {
        return \Model::factory(User::class)->findMany();
        //return $this->createModel()->newQuery()->get()->all();
    }

    /**
     * Returns all users who belong to
     * a group.
     *
     * @param  \Cartalyst\Sentry\Groups\GroupInterface  $group
     * @return array
     */
    public function findAllInGroup(GroupInterface $group)
    {
        return $group->users();//->get();
    }

    /**
     * Returns all users with access to
     * a permission(s).
     *
     * @param  string|array  $permissions
     * @return array
     */
    public function findAllWithAccess($permissions)
    {
        return array_filter(
            $this->findAll(),
            function ($user) use ($permissions) {
                return $user->hasAccess($permissions);
            }
        );
    }

    /**
     * Returns all users with access to
     * any given permission(s).
     *
     * @param  array  $permissions
     * @return array
     */
    public function findAllWithAnyAccess(array $permissions)
    {
        return array_filter(
            $this->findAll(),
            function ($user) use ($permissions) {
                return $user->hasAnyAccess($permissions);
            }
        );
    }

    /**
     * Creates a user.
     *
     * @param  array  $credentials
     * @return \Cartalyst\Sentry\Users\UserInterface
     */
    public function create(array $credentials)
    {
        $user = $this->createModel();
        $user->hydrate($credentials);
        $user->save();

        return $user;
    }

    /**
     * Returns an empty user object.
     *
     * @return \Cartalyst\Sentry\Users\UserInterface
     */
    public function getEmptyUser()
    {
        return $this->createModel();
    }

    /**
     * Create a new instance of the model.
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        return \Model::factory(User::class)->create();
    }
    
    /**
     * Statically sets the hasher with the model.
     *
     * @return void
     */
    public function setupHasherWithModel()
    {
        if (method_exists(User::class, 'setHasher')) {
            forward_static_call_array(array(User::class, 'setHasher'), array($this->hasher));
        }
    }

}

