<?php namespace Braindump\Api\Model\Sentry\Paris;

use Braindump\Api\Model\Sentry\Paris\DateTimeModel;
use Cartalyst\Sentry\Groups\GroupInterface;
use Cartalyst\Sentry\Hashing\HasherInterface;
use Cartalyst\Sentry\Users\LoginRequiredException;
use Cartalyst\Sentry\Users\PasswordRequiredException;
use Cartalyst\Sentry\Users\UserAlreadyActivatedException;
use Cartalyst\Sentry\Users\UserExistsException;
use Cartalyst\Sentry\Users\UserInterface;

use Braindump\Api\Model\UserConfiguration;
use Braindump\Api\Model\Notebook;

class User extends DateTimeModel implements UserInterface
{
    public static $_table = 'users';

    /***
     * Paris relation (Braindump specific)
     */
    public function configuration()
    {
        return $this->has_one(UserConfiguration::class, 'user_id');
    }

    /***
     * Paris relation (Braindump specific)
     */
    public function notebooks()
    {
        return $this->has_many(Notebook::class, 'user_id');
    }

    /**
     * Attributes that should be hashed.
     *
     * @var array
     */
    protected $hashableAttributes = array(
        'password',
        'persist_code',
    );

    /**
     * Allowed permissions values.
     *
     * Possible options:
     *   -1 => Deny (adds to array, but denies regardless of user's group).
     *    0 => Remove.
     *    1 => Add.
     *
     * @var array
     */
    protected $allowedPermissionsValues = array(-1, 0, 1);

    /**
     * The login attribute.
     *
     * @var string
     */
    protected static $loginAttribute = 'login';

    /**
     * The hasher the model uses.
     *
     * @var \Cartalyst\Sentry\Hashing\HasherInterface
     */
    protected static $hasher;

    /**
     * The user groups.
     *
     * @var array
     */
    protected $userGroups;

    /**
     * The user merged permissions.
     *
     * @var array
     */
    protected $mergedPermissions;

    /**
     * Returns the user's ID.
     *
     * @return  mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the name for the user's login.
     *
     * @return string
     */
    public function getLoginName()
    {
        return static::$loginAttribute;
    }

    /**
     * Returns the user's login.
     *
     * @return mixed
     */
    public function getLogin()
    {
        return $this->{$this->getLoginName()};
    }

    /**
     * Returns the name for the user's password.
     *
     * @return string
     */
    public function getPasswordName()
    {
        return 'password';
    }

    /**
     * Returns the user's password (hashed).
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->{$this->getPasswordName()};
    }

    /**
     * Returns permissions for the user.
     *
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Check if the user is activated.
     *
     * @return bool
     */
    public function isActivated()
    {
        return (bool) $this->activated;
    }

    /**
     * Get mutator for giving the activated property.
     *
     * @param  mixed  $activated
     * @return bool
     */
    public function getActivatedAttribute($activated)
    {
        return (bool) $activated;
    }

    /**
     * Mutator for giving permissions.
     *
     * @param  mixed  $permissions
     * @return array  $_permissions
     */
    public function getPermissionsAttribute($permissions)
    {
        if (!$permissions) {
            return array();
        }

        if (is_array($permissions)) {
            return $permissions;
        }

        if (!$_permissions = json_decode($permissions, true)) {
            throw new \InvalidArgumentException("Cannot JSON decode permissions [$permissions].");
        }

        return $_permissions;
    }

    /**
     * Mutator for taking permissions.
     *
     * @param  array  $permissions
     * @return string
     */
    public function setPermissionsAttribute(array $permissions)
    {
        // Merge permissions
        $permissions = array_merge($this->getPermissions(), $permissions);

        // Loop through and adjust permissions as needed
        foreach ($permissions as $permission => &$value) {
            // Lets make sure there is a valid permission value
            if (!in_array($value = (int) $value, $this->allowedPermissionsValues)) {
                throw new \InvalidArgumentException("Invalid value [$value] for permission [$permission] given.");
            }

            // If the value is 0, delete it
            if ($value === 0) {
                unset($permissions[$permission]);
            }
        }

        //$this->attributes['permissions'] = ( ! empty($permissions)) ? json_encode($permissions) : '';
        $this->orm->set('permissions', (!empty($permissions)) ? json_encode($permissions) : '');
    }

    /**
     * Checks if the user is a super user - has
     * access to everything regardless of permissions.
     *
     * @return bool
     */
    public function isSuperUser()
    {
        return $this->hasPermission('superuser');
    }

    /**
     * Validates the user and throws a number of
     * Exceptions if validation fails.
     *
     * @return bool
     * @throws \Cartalyst\Sentry\Users\LoginRequiredException
     * @throws \Cartalyst\Sentry\Users\PasswordRequiredException
     * @throws \Cartalyst\Sentry\Users\UserExistsException
     */
    public function validate()
    {
        if (!$login = $this->{static::$loginAttribute}) {
            throw new LoginRequiredException("A login is required for a user, none given.");
        }

        // if (!$password = $this->getPassword()) {
        //     throw new PasswordRequiredException("A password is required for user [$login], none given.");
        // }

        // Check if the user already exists
        //$query = $this->newQuery();
        //$persistedUser = $query->where($this->getLoginName(), '=', $login)->first();
        $persistedUser = \Model::factory(User::class)
                    ->where_equal($this->getLoginName(), $login)
                    ->find_one();

        if ($persistedUser and $persistedUser->getId() != $this->getId()) {
            throw new UserExistsException("A user already exists with login [$login], logins must be unique for users.");
        }

        return true;
    }

    /**
     * Saves the user.
     *
     * @param  array  $options
     * @return bool
     */
    public function save() //array $options = array())
    {
        $this->validate();

        return parent::save();
    }

    /**
     * Delete the user.
     *
     * @return bool
     */
    public function delete()
    {
        $groups = $this->groups();
        $result = false;
        
        \ORM::get_db()->beginTransaction();

        try {
            // TODO: Check why user_groups table is not updated
            foreach ($groups as $group) {
                $this->removeGroup($group);
            }

            $result = parent::delete();
            \ORM::get_db()->commit();
            return $result;

        } catch (\Exception $e) {
            \ORM::get_db()->rollback();
            return $result;
        }
    }

    /**
     * Gets a code for when the user is
     * persisted to a cookie or session which
     * identifies the user.
     *
     * @return string
     */
    public function getPersistCode()
    {
        $this->persist_code = $this->getRandomString();

        // Our code got hashed
        $persistCode = $this->persist_code;

        $this->save();

        return $persistCode;
    }

    /**
     * Checks the given persist code.
     *
     * @param  string  $persistCode
     * @return bool
     */
    public function checkPersistCode($persistCode)
    {
        if (!$persistCode) {
            return false;
        }

        return $persistCode == $this->persist_code;
    }

    /**
     * Get an activation code for the given user.
     *
     * @return string
     */
    public function getActivationCode()
    {
        $this->activation_code = $activationCode = $this->getRandomString();

        $this->save();

        return $activationCode;
    }

    /**
     * Attempts to activate the given user by checking
     * the activate code. If the user is activated already,
     * an Exception is thrown.
     *
     * @param  string  $activationCode
     * @return bool
     * @throws \Cartalyst\Sentry\Users\UserAlreadyActivatedException
     */
    public function attemptActivation($activationCode)
    {
        if ($this->activated) {
            throw new UserAlreadyActivatedException('Cannot attempt activation on an already activated user.');
        }

        if ($activationCode == $this->activation_code) {
            $this->activation_code = null;
            $this->activated       = true;
            $this->activated_at    = $this->freshTimestamp();
            return $this->save();
        }

        return false;
    }

    /**
     * Checks the password passed matches the user's password.
     *
     * @param  string  $password
     * @return bool
     */
    public function checkPassword($password)
    {
        return $this->checkHash($password, $this->getPassword());
    }

    /**
     * Get a reset password code for the given user.
     *
     * @return string
     */
    public function getResetPasswordCode()
    {
        $this->reset_password_code = $resetCode = $this->getRandomString();

        $this->save();

        return $resetCode;
    }

    /**
     * Checks if the provided user reset password code is
     * valid without actually resetting the password.
     *
     * @param  string  $resetCode
     * @return bool
     */
    public function checkResetPasswordCode($resetCode)
    {
        return ($this->reset_password_code == $resetCode);
    }

    /**
     * Attempts to reset a user's password by matching
     * the reset code generated with the user's.
     *
     * @param  string  $resetCode
     * @param  string  $newPassword
     * @return bool
     */
    public function attemptResetPassword($resetCode, $newPassword)
    {
        if ($this->checkResetPasswordCode($resetCode)) {
            $this->password = $newPassword;
            $this->reset_password_code = null;
            return $this->save();
        }

        return false;
    }

    /**
     * Wipes out the data associated with resetting
     * a password.
     *
     * @return void
     */
    public function clearResetPassword()
    {
        if ($this->reset_password_code) {
            $this->reset_password_code = null;
            $this->save();
        }
    }

    /**
     * Returns an array of groups which the given
     * user belongs to.
     *
     * @return array
     */
    public function getGroups()
    {
        if (!$this->userGroups) {
            $this->userGroups = $this->groups()->find_many();
        }

        return $this->userGroups;
    }

    /**
     * Clear the cached permissions attribute.
     */
    public function invalidateMergedPermissionsCache()
    {
        $this->mergedPermissions = null;
    }

    /**
     * Clear the cached user groups attribute.
     */
    public function invalidateUserGroupsCache()
    {
        $this->userGroups = null;
    }
    
    /**
     * Adds the user to the given group.
     *
     * @param \Cartalyst\Sentry\Groups\GroupInterface  $group
     * @return bool
     */
    public function addGroup(GroupInterface $group)
    {
        if (!$this->inGroup($group)) {
            $groupUser = \Model::factory(GroupUser::class)->create();
            $groupUser->group_id = $group->getID();
            $groupUser->user_id = $this->getID();
            $groupUser->save();

            $this->invalidateUserGroupsCache();
            $this->invalidateMergedPermissionsCache();
        }

        return true;
    }

    /**
     * Removes the user from the given group.
     *
     * @param \Cartalyst\Sentry\Groups\GroupInterface  $group
     * @return bool
     */
    public function removeGroup(GroupInterface $group)
    {
        if ($this->inGroup($group)) {
            $groupUser = \Model::factory(GroupUser::class)
                ->where('group_id', $group->getID())
                ->where('user_id', $this->getID())
                ->find_one();

            if ($groupUser) {
                $groupUser->delete();
            }
                
            $this->invalidateUserGroupsCache();
            $this->invalidateMergedPermissionsCache();
        }

        return true;
    }

    /**
     * See if the user is in the given group.
     *
     * @param \Cartalyst\Sentry\Groups\GroupInterface  $group
     * @return bool
     */
    public function inGroup(GroupInterface $group)
    {
        foreach ($this->getGroups() as $_group) {
            if ($_group->getId() == $group->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns an array of merged permissions for each
     * group the user is in.
     *
     * @return array
     */
    public function getMergedPermissions()
    {
        if (!$this->mergedPermissions) {
            $permissions = array();

            foreach ($this->getGroups() as $group) {
                $permissions = array_merge($permissions, $group->getPermissions());
            }

            $this->mergedPermissions = array_merge($permissions, $this->getPermissions());
        }

        return $this->mergedPermissions;
    }

    /**
     * See if a user has access to the passed permission(s).
     * Permissions are merged from all groups the user belongs to
     * and then are checked against the passed permission(s).
     *
     * If multiple permissions are passed, the user must
     * have access to all permissions passed through, unless the
     * "all" flag is set to false.
     *
     * Super users have access no matter what.
     *
     * @param  string|array  $permissions
     * @param  bool  $all
     * @return bool
     */
    public function hasAccess($permissions, $all = true)
    {
        if ($this->isSuperUser()) {
            return true;
        }

        return $this->hasPermission($permissions, $all);
    }

    /**
     * See if a user has access to the passed permission(s).
     * Permissions are merged from all groups the user belongs to
     * and then are checked against the passed permission(s).
     *
     * If multiple permissions are passed, the user must
     * have access to all permissions passed through, unless the
     * "all" flag is set to false.
     *
     * Super users DON'T have access no matter what.
     *
     * @param  string|array  $permissions
     * @param  bool  $all
     * @return bool
     */
    public function hasPermission($permissions, $all = true)
    {
        $mergedPermissions = $this->getMergedPermissions();

        if (!is_array($permissions)) {
            $permissions = (array) $permissions;
        }

        foreach ($permissions as $permission) {
            // We will set a flag now for whether this permission was
            // matched at all.
            $matched = true;

            // Now, let's check if the permission ends in a wildcard "*" symbol.
            // If it does, we'll check through all the merged permissions to see
            // if a permission exists which matches the wildcard.
            if ((strlen($permission) > 1) and ends_with($permission, '*')) {

                $matched = false;

                foreach ($mergedPermissions as $mergedPermission => $value) {
                    // Strip the '*' off the end of the permission.
                    $checkPermission = substr($permission, 0, -1);

                    // We will make sure that the merged permission does not
                    // exactly match our permission, but starts with it.
                    if ($checkPermission != $mergedPermission and starts_with($mergedPermission, $checkPermission) and $value == 1) {
                        $matched = true;
                        break;
                    }
                }
            } elseif ((strlen($permission) > 1) and starts_with($permission, '*')) {
                
                $matched = false;

                foreach ($mergedPermissions as $mergedPermission => $value) {
                    // Strip the '*' off the beginning of the permission.
                    $checkPermission = substr($permission, 1);

                    // We will make sure that the merged permission does not
                    // exactly match our permission, but ends with it.
                    if ($checkPermission != $mergedPermission and ends_with($mergedPermission, $checkPermission) and $value == 1) {
                        $matched = true;
                        break;
                    }
                }

            } else {

                $matched = false;

                foreach ($mergedPermissions as $mergedPermission => $value) {
                    // This time check if the mergedPermission ends in wildcard "*" symbol.
                    if ((strlen($mergedPermission) > 1) and ends_with($mergedPermission, '*')) {
                        $matched = false;

                        // Strip the '*' off the end of the permission.
                        $checkMergedPermission = substr($mergedPermission, 0, -1);

                        // We will make sure that the merged permission does not
                        // exactly match our permission, but starts with it.
                        if ($checkMergedPermission != $permission and starts_with($permission, $checkMergedPermission) and $value == 1) {
                            $matched = true;
                            break;
                        }
                    // Otherwise, we'll fallback to standard permissions checking where
                    // we match that permissions explicitly exist.
                    } elseif ($permission == $mergedPermission and $mergedPermissions[$permission] == 1) {
                        $matched = true;
                        break;
                    }
                }
            }

            // Now, we will check if we have to match all
            // permissions or any permission and return
            // accordingly.
            if ($all === true and $matched === false) {
                return false;
            } elseif ($all === false and $matched === true) {
                return true;
            }
        }

        if ($all === false) {
            return false;
        }

        return true;
    }

    /**
     * Returns if the user has access to any of the
     * given permissions.
     *
     * @param  array  $permissions
     * @return bool
     */
    public function hasAnyAccess(array $permissions)
    {
        return $this->hasAccess($permissions, false);
    }

    /**
     * Records a login for the user.
     *
     * @return void
     */
    public function recordLogin()
    {
        $this->last_login = $this->freshTimestamp();
        $this->save();
    }

    /**
     * Returns the relationship between users and groups.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->has_many_through(
            Group::class, 
            GroupUser::class, 
            GroupUser::USER_KEY, 
            GroupUser::GROUP_KEY);
        ///return $this->belongsToMany(static::$groupModel, static::$userGroupsPivot);
    }

    /**
     * Check string against hashed string.
     *
     * @param  string  $string
     * @param  string  $hashedString
     * @return bool
     * @throws RuntimeException
     */
    public function checkHash($string, $hashedString)
    {
        if (!static::$hasher) {
            throw new \RuntimeException("A hasher has not been provided for the user.");
        }

        return static::$hasher->checkhash($string, $hashedString);
    }

    /**
     * Hash string.
     *
     * @param  string  $string
     * @return string
     * @throws RuntimeException
     */
    public function hash($string)
    {
        if (!static::$hasher) {
            throw new \RuntimeException("A hasher has not been provided for the user.");
        }

        return static::$hasher->hash($string);
    }

    /**
     * Generate a random string.
     *
     * @return string
     */
    public function getRandomString($length = 42)
    {
        // We'll check if the user has OpenSSL installed with PHP. If they do
        // we'll use a better method of getting a random string. Otherwise, we'll
        // fallback to a reasonably reliable method.
        if (function_exists('openssl_random_pseudo_bytes')) {
            // We generate twice as many bytes here because we want to ensure we have
            // enough after we base64 encode it to get the length we need because we
            // take out the "/", "+", and "=" characters.
            $bytes = openssl_random_pseudo_bytes($length * 2);

            // We want to stop execution if the key fails because, well, that is bad.
            if ($bytes === false) {
                throw new \RuntimeException('Unable to generate random string.');
            }

            return substr(str_replace(array('/', '+', '='), '', base64_encode($bytes)), 0, $length);
        }

        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

    /**
     * Returns an array of hashable attributes.
     *
     * @return array
     */
    public function getHashableAttributes()
    {
        return $this->hashableAttributes;
    }

    /**
     * Get the attributes that should be converted to dates.
     *
     * @return array
     */
    public function getDates()
    {
        return array_merge(parent::getDates(), array('activated_at', 'last_login'));
    }

    public function as_array()
    {
        return $this->toArray();
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $result = parent::as_array();

        if (isset($result['activated'])) {
            $result['activated'] = $this->getActivatedAttribute($result['activated']);
        }
        if (isset($result['permissions'])) {
            $result['permissions'] = $this->getPermissionsAttribute($result['permissions']);
        }
        if (isset($result['suspended_at'])) {
            $result['suspended_at'] = $result['suspended_at']->format('Y-m-d H:i:s');
        }

        return $result;
    }

    /**
     * Sets the hasher for the user.
     *
     * @param \Cartalyst\Sentry\Hashing\HasherInterface $hasher
     * @return void
     */
    public static function setHasher(HasherInterface $hasher)
    {
        static::$hasher = $hasher;
    }

    /**
     * Returns the hasher for the user.
     *
     * @return \Cartalyst\Sentry\Hashing\HasherInterface
     */
    public static function getHasher()
    {
        return static::$hasher;
    }

    /**
     * Unset the hasher used by the user.
     *
     * @return void
     */
    public static function unsetHasher()
    {
        static::$hasher = null;
    }

    /**
     * Override the login attribute for all models instances.
     *
     * @param  string  $loginAttribute
     * @return void
     */
    public static function setLoginAttributeName($loginAttribute)
    {
        static::$loginAttribute = $loginAttribute;
    }

    /**
     * Get the current login attribute for all model instances.
     *
     * @return string
     */
    public static function getLoginAttributeName()
    {
        return static::$loginAttribute;
    }

    public function __get($property)
    {
        if ($property == 'permissions') {
            return $this->getPermissionsAttribute($this->orm->get($property));
        } else {
            return parent::__get($property);
        }
    }

    public function __set($property, $value)
    {
        if ($property == 'permissions') {
            return $this->setPermissionsAttribute($value);
        } else {

            if (in_array($property, $this->hashableAttributes) and !empty($value)) {
                $value = $this->hash($value);
            }
    
            return parent::__set($property, $value);
        }
    }

    /***
     * Hydrate (populate) while hashing the hashable attributes
     */
    public function hydrate($data = [])
    {
        // check if array contains hashable attributes
        foreach ($data as $property => &$value) {
            if (in_array($property, $this->hashableAttributes) and !empty($value)) {
                $value = $this->hash($value);
            }
        }

        return parent::hydrate($data);
    }

    /***
     * Hydrate (populate) withoud (re-)hashing the hashable attributes
     */
    public function hydratePlain($data = []) {
        return parent::hydrate($data);
    }
}

class GroupUser extends \Model
{
    const USER_KEY = 'user_id';
    const GROUP_KEY = 'group_id';
    
    public static $_table = 'users_groups';
}

function starts_with($haystack, $needle)
{
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function ends_with($haystack, $needle)
{
    // search forward starting from end minus needle length characters
    return $needle === "" || strpos($haystack, $needle, strlen($haystack) - strlen($needle)) !== false;
}
