<?php namespace Cartalyst\Sentry\Tests;

require_once(__DIR__ . '../../../model/Sentry/Paris/Group.php');

use Cartalyst\Sentry\Groups\Paris\Group;

/***
 * Based on Eloquent tests of https://github.com/cartalyst/sentry/
 */
class ParisGroupTest extends \Braindump\Api\Test\Integration\AbstractDbTest
{

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        // TODO: move files to sentry dir
        return $this->createFlatXMLDataSet(dirname(__FILE__) . '/files/groups-seed.xml');
    }

    public function testGroupId()
    {
        $group = \Model::factory(Group::CLASS_NAME)->create();
        $group->id = 123;

        $this->assertEquals(123, $group->getId());
    }

    public function testGroupName()
    {
        $group = \Model::factory(Group::CLASS_NAME)->create();
        $group->name = 'foo';

        $this->assertEquals('foo', $group->getName());
    }

    // public function testSettingPermissions()
    // {
    //  $permissions = array(
    //      'foo' => 1,
    //      'bar' => 1,
    //      'baz' => 1,
    //      'qux' => 1,
    //  );

    //  $group = new Group;

    //  $expected = '{"foo":1,"bar":1,"baz":1,"qux":1}';

    //  $this->assertEquals($expected, $group->setPermissions($permissions));
    // }

    // public function testSettingPermissionsWhenSomeAreSetTo0()
    // {
    //  $permissions = array(
    //      'foo' => 1,
    //      'bar' => 1,
    //      'baz' => 0,
    //      'qux' => 1,
    //  );

    //  $group = new Group;

    //  $expected = '{"foo":1,"bar":1,"qux":1}';

    //  $this->assertEquals($expected, $group->setPermissions($permissions));
    // }

    public function testPermissionsAreMergedAndRemovedProperly()
    {
        $group = \Model::factory(Group::CLASS_NAME)->create();
        $group->permissions = array(
            'foo' => 1,
            'bar' => 1,
        );

        $group->permissions = array(
            'baz' => 1,
            'qux' => 1,
            'foo' => 0,
        );

        $expected = array(
            'bar' => 1,
            'baz' => 1,
            'qux' => 1,
        );

        $this->assertEquals($expected, $group->permissions);
    }

    public function testPermissionsAreCastAsAnArrayWhenTheModelIs()
    {
        $group = \Model::factory(Group::CLASS_NAME)->create();
        $group->name = 'foo';
        $group->permissions = array(
            'bar' => 1,
            'baz' => 1,
            'qux' => 1,
        );

        $expected = array(
            'name' => 'foo',
            'permissions' => array(
                'bar' => 1,
                'baz' => 1,
                'qux' => 1,
            ),
        );

        $this->assertEquals($expected, $group->toArray());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testExceptionIsThrownForInvalidPermissionsDecoding()
    {
        $json = '{"foo":1,"bar:1';
        
        $group = \Model::factory(Group::CLASS_NAME)->create();

        $group->getPermissionsAttribute($json);
    }

    /**
     * Regression test for https://github.com/cartalyst/sentry/issues/103
     */
    public function testSettingPermissionsWhenPermissionsAreStrings()
    {
        
        $group = \Model::factory(Group::CLASS_NAME)->create();
        $group->permissions = array(
            'admin'    => '1',
            'foo'      => '0',
        );

        $expected = array(
            'admin'     => 1,
        );

        $this->assertEquals($expected, $group->permissions);
    }

    /**
     * Regression test for https://github.com/cartalyst/sentry/issues/103
     */
    public function testSettingPermissionsWhenAllPermissionsAreZero()
    {
        $group = \Model::factory(Group::CLASS_NAME)->create();

        $group->permissions = array(
            'admin'     => 0,
        );

        $this->assertEquals(array(), $group->permissions);
    }

    // TODO: verify that db will be hit during validation
    /*public function testValidation()
    {
        //$group = m::mock('Cartalyst\Sentry\Groups\Paris\Group[newQuery]');
        $group = \Model::factory(Group::CLASS_NAME)->create();

        $group->name = 'foo';

        $query = m::mock('StdClass');
        $query->shouldReceive('where')->with('name', '=', 'foo')->once()->andReturn($query);
        $query->shouldReceive('first')->once()->andReturn(null);

        $group->shouldReceive('newQuery')->once()->andReturn($query);
        
        $group->validate();
    }*/

    /**
     * @expectedException Cartalyst\Sentry\Groups\NameRequiredException
     */
    public function testValidationThrowsExceptionForMissingName()
    {
        $group = \Model::factory(Group::CLASS_NAME)->create();
        $group->validate();
    }

    /**
     * @expectedException Cartalyst\Sentry\Groups\GroupExistsException
     */
    public function testValidationThrowsExceptionForDuplicateNameOnNonExistent()
    {
        $group = \Model::factory(Group::CLASS_NAME)->create();
        $group->name = 'foo';

        $group->validate();
    }

    /**
     * @expectedException Cartalyst\Sentry\Groups\GroupExistsException
     */
    public function testValidationThrowsExceptionForDuplicateNameOnExistent()
    {
        $group = \Model::factory(Group::CLASS_NAME)->create();
        $group->id   = 124;
        $group->name = 'foo';

        $group->validate();
    }

    public function testValidationDoesNotThrowAnExceptionIfPersistedGroupIsThisGroup()
    {
        $group = \Model::factory(Group::CLASS_NAME)->create();
        $group->id   = 123;
        $group->name = 'foo';

        $group->validate();
    }

    public function testPermissionsWithArrayCastingAndJsonCasting()
    {
        $group = \Model::factory(Group::CLASS_NAME)->create();
        $group->name = 'foo';
        $group->permissions = array(
            'foo' => 1,
            'bar' => 0,
            'baz' => 1,
        );

        $expected = array(
            'name'        => 'foo',
            'permissions' => array(
                'foo' => 1,
                'baz' => 1,
            ),
        );

        $this->assertEquals($expected, $group->toArray());

        $expected = json_encode($expected);
    }

    public function testDeletingGroupDetachesAllUserRelationships()
    {
        /*$relationship = m::mock('StdClass');
        $relationship->shouldReceive('detach')->once();

        //$group = m::mock('Cartalyst\Sentry\Groups\EloqueParisnt\Group[users]');
        //$group = new Group;
        $group = \Model::factory(Group::CLASS_NAME)->create();

        $group->shouldReceive('users')->once()->andReturn($relationship);

        $group->delete();*/
    }

}
