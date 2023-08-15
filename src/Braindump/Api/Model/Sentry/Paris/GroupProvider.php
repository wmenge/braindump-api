<?php namespace Braindump\Api\Model\Sentry\Paris;

use Cartalyst\Sentry\Groups\GroupInterface;
use Cartalyst\Sentry\Groups\GroupNotFoundException;
use Cartalyst\Sentry\Groups\ProviderInterface;

/***
 * Based on Eloquent implementation of https://github.com/cartalyst/sentry/
 */
class GroupProvider implements ProviderInterface
{
    /**
     * Find the group by ID.
     *
     * @param  int  $id
     * @return \Cartalyst\Sentry\Groups\GroupInterface  $group
     * @throws \Cartalyst\Sentry\Groups\GroupNotFoundException
     */
    public function findById($id)
    {
        $group = \Model::factory(Group::class)
                    ->where_equal('id', $id)
                    ->find_one();

        if (!$group) {
            throw new GroupNotFoundException("A group could not be found with ID [$id].");
        }

        return $group;
    }

    /**
     * Find the group by name.
     *
     * @param  string  $name
     * @return \Cartalyst\Sentry\Groups\GroupInterface  $group
     * @throws \Cartalyst\Sentry\Groups\GroupNotFoundException
     */
    public function findByName($name)
    {
         $group = \Model::factory(Group::class)
                    ->where_equal('name', $name)
                    ->find_one();

        if (!$group) {
            throw new GroupNotFoundException("A group could not be found with the name [$name].");
        }

        return $group;
    }

    /**
     * Returns all groups.
     *
     * @return array  $groups
     */
    public function findAll()
    {
        return \Model::factory(Group::class)->find_many();
    }

    /**
     * Creates a group.
     *
     * @param  array  $attributes
     * @return \Cartalyst\Sentry\Groups\GroupInterface
     */
    public function create(array $attributes)
    {
        $group = $this->createModel();
        $group->hydrate($attributes);
        $group->save();
        return $group;
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        return \Model::factory(Group::class)->create();
    }

}
