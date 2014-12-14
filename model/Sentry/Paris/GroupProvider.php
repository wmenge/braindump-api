<?php namespace Cartalyst\Sentry\Groups\Paris;

require_once(__DIR__ . '/Group.php');

use Cartalyst\Sentry\Groups\GroupInterface;
use Cartalyst\Sentry\Groups\GroupNotFoundException;
use Cartalyst\Sentry\Groups\ProviderInterface;

/***
 * Based on Eloquent implementation of https://github.com/cartalyst/sentry/
 */
class GroupProvider implements ProviderInterface
{
    /**
     * The Paris group model.
     *
     * @var string
     */
    protected $model = '\Cartalyst\Sentry\Groups\Paris\Group';

    /**
     * Create a new Paris Group provider.
     *
     * @param  string  $model
     * @return void
     */
    public function __construct($model = null)
    {
        if (isset($model)) {
            $this->model = $model;
        }
    }

    /**
     * Find the group by ID.
     *
     * @param  int  $id
     * @return \Cartalyst\Sentry\Groups\GroupInterface  $group
     * @throws \Cartalyst\Sentry\Groups\GroupNotFoundException
     */
    public function findById($id)
    {
        $group = \Model::factory($this->model)
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
         $group = \Model::factory($this->model)
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
        return \Model::factory($this->model)->find_many();
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
        //$class = '\\'.ltrim($this->model, '\\');
        return \Model::factory(Group::CLASS_NAME)->create();
        

        //return new $class;
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
