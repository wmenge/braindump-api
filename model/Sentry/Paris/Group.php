<?php namespace Cartalyst\Sentry\Groups\Paris;

use Cartalyst\Sentry\Groups\NameRequiredException;
use Cartalyst\Sentry\Groups\GroupExistsException;
use Cartalyst\Sentry\Groups\GroupInterface;

/*class GroupUser extends \Model
{
    public static $_table = 'users_groups';
}*/

/***
 * Based on Eloquent implementation of https://github.com/cartalyst/sentry/
 */
class Group extends \Model implements GroupInterface {

    const CLASS_NAME = 'Cartalyst\Sentry\Groups\Paris\Group';

    public static $_table = 'groups';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = array();

    /**
     * Allowed permissions values.
     *
     * Possible options:
     *    0 => Remove.
     *    1 => Add.
     *
     * @var array
     */
    protected $allowedPermissionsValues = array(0, 1);

    /**
     * The Paris user model.
     *
     * @var string
     */
    protected static $userModel = 'Cartalyst\Sentry\Users\Paris\User';

    /**
     * Returns the group's ID.
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the group's name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns permissions for the group.
     *
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * See if a group has access to the passed permission(s).
     *
     * If multiple permissions are passed, the group must
     * have access to all permissions passed through, unless the
     * "all" flag is set to false.
     *
     * @param  string|array  $permissions
     * @param  bool  $all
     * @return bool
     */
    public function hasAccess($permissions, $all = true)
    {
        $groupPermissions = $this->getPermissions();

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

                foreach ($groupPermissions as $groupPermission => $value) {
                    // Strip the '*' off the end of the permission.
                    $checkPermission = substr($permission, 0, -1);

                    // We will make sure that the merged permission does not
                    // exactly match our permission, but starts with it.
                    if ($checkPermission != $groupPermission and starts_with($groupPermission, $checkPermission) and $value == 1) {
                        $matched = true;
                        break;
                    }
                }
            // Now, let's check if the permission starts in a wildcard "*" symbol.
            // If it does, we'll check through all the merged permissions to see
            // if a permission exists which matches the wildcard.
            } elseif ((strlen($permission) > 1) and starts_with($permission, '*')) {
                $matched = false;

                foreach ($groupPermissions as $groupPermission => $value) {
                    // Strip the '*' off the start of the permission.
                    $checkPermission = substr($permission, 1);

                    // We will make sure that the merged permission does not
                    // exactly match our permission, but ends with it.
                    if ($checkPermission != $groupPermission and ends_with($groupPermission, $checkPermission) and $value == 1) {
                        $matched = true;
                        break;
                    }
                }
            } else {
                $matched = false;

                foreach ($groupPermissions as $groupPermission => $value) {
                    // This time check if the groupPermission ends in wildcard "*" symbol.
                    if ((strlen($groupPermission) > 1) and ends_with($groupPermission, '*')) {
                        $matched = false;

                        // Strip the '*' off the end of the permission.
                        $checkGroupPermission = substr($groupPermission, 0, -1);

                        // We will make sure that the merged permission does not
                        // exactly match our permission, but starts wtih it.
                        if ($checkGroupPermission != $permission and starts_with($permission, $checkGroupPermission) and $value == 1) {
                            $matched = true;
                            break;
                        }
                    // Otherwise, we'll fallback to standard permissions checking where
                    // we match that permissions explicitly exist.
                    } elseif ($permission == $groupPermission and $groupPermissions[$permission] == 1) {
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

        return $all;
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
     * Returns the relationship between groups and users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->has_many_through(static::$userModel);
    }

    /**
     * Set the Eloquent model to use for user relationships.
     *
     * @param  string  $model
     * @return void
     */
    public static function setUserModel($model)
    {
        static::$userModel = $model;
    }

    /**
     * Saves the group.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = array())
    {
        $this->validate();
        return parent::save();
    }

    /**
     * Delete the group.
     *
     * @return bool
     */
    public function delete()
    {
        //$this->users()->detach();
        return parent::delete();
    }

    /**
     * Mutator for giving permissions.
     *
     * @param  mixed $permissions
     * @return array
     * @throws \InvalidArgumentException
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
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setPermissionsAttribute(array $permissions)
    {
        // Merge permissions
        $permissions = array_merge($this->getPermissions(), $permissions);

        // Loop through and adjust permissions as needed
        foreach ($permissions as $permission => &$value) {
            // Lets make sure their is a valid permission value
            if (!in_array($value = (int) $value, $this->allowedPermissionsValues)) {
                throw new \InvalidArgumentException("Invalid value [$value] for permission [$permission] given.");
            }

            // If the value is 0, delete it
            if ($value === 0) {
                unset($permissions[$permission]);
            }
        }

        $this->orm->set('permissions', (!empty($permissions)) ? json_encode($permissions) : '');
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = parent::as_array();

        if (isset($attributes['permissions'])) {
            $attributes['permissions'] = $this->getPermissionsAttribute($attributes['permissions']);
        }

        return $attributes;
    }

    /**
     * Validates the group and throws a number of
     * Exceptions if validation fails.
     *
     * @return bool
     * @throws \Cartalyst\Sentry\Groups\NameRequiredException
     * @throws \Cartalyst\Sentry\Groups\GroupExistsException
     */
    public function validate()
    {
        // Check if name field was passed
        if (!$name = $this->name) {
            throw new NameRequiredException("A name is required for a group, none given.");
        }

        // Check if group already exists
        $persistedGroup = \Model::factory(Group::CLASS_NAME)
                    ->where_equal('name', $name)
                    ->find_one();

        if ($persistedGroup and $persistedGroup->getId() != $this->getId()) {
            throw new GroupExistsException("A group already exists with name [$name], names must be unique for groups.");
        }

        return true;
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
            return parent::__set($property, $value);
        }
    }
}
