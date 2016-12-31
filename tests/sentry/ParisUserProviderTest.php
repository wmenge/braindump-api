<?php namespace Cartalyst\Sentry\Tests;

require_once(__DIR__ . '../../../model/Sentry/Paris/UserProvider.php');

use Mockery as m;
use Cartalyst\Sentry\Users\Paris\UserProvider as Provider;
use Cartalyst\Sentry\Users\Paris\User;
use Cartalyst\Sentry\Hashing\HasherInterface;
use UserModelStub1;
use UserModelStub2;
use PHPUnit_Framework_TestCase;

class ParisUserProviderTest extends \Braindump\Api\Test\Integration\AbstractDbTest
{
    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__) . '/files/users-seed.xml');
    }

    /**
     * Setup resources and dependencies.
     *
     * @return void
     */
    public static function setUpBeforeClass()
    {
        require_once __DIR__.'/stubs/users/UserModelStubs.php';
    }

    public function setup()
    {
        parent::setUp();
        $this->hasher = $this->createMock('Cartalyst\Sentry\Hashing\HasherInterface', array('hash', 'checkhash'));
        $this->hasher->method('hash')->willReturn('hashed_password_here');
        $this->provider = new Provider($this->hasher);
        User::setHasher($this->hasher);
    }

    public function testFindingById()
    {
        $user = $user = \Model::factory(User::CLASS_NAME)->create();
        $user->id = '1';
        $user->email = 'test@test.com';
        $user->password = 'test';
        $user->save();

        $this->assertEquals($user->id, $this->provider->findById(1)->id);
        $this->assertEquals($user->email, $this->provider->findById(1)->email);
    }

    /**
     * @expectedException Cartalyst\Sentry\Users\UserNotFoundException
     */
    public function testFailedFindingByIdThrowsException()
    {
        $this->provider->findById(1);
    }

    public function testFindingByName()
    {
        $user = $this->provider->findById(123);
        $this->assertEquals($user->id, $this->provider->findByLogin('foo@bar.com')->id);
        $this->assertEquals($user->email, $this->provider->findByLogin('foo@bar.com')->email);
    }

    /**
     * @expectedException Cartalyst\Sentry\Users\UserNotFoundException
     */
    public function testFailedFindingByNameThrowsException()
    {
        $this->provider->findByLogin('footest@bar.com');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFindingByCredentialsFailsWithoutLoginColumn()
    {
        $this->provider->findByCredentials([ 'not_foo' => 'ff' ]);
    }

    /**
     * @expectedException Cartalyst\Sentry\Users\UserNotFoundException
     */
    public function testFindingByCredentialsFailsWhenModelIsNull()
    {
        $result = $this->provider->findByCredentials([ 'email' => 'fooval' ]);
    }

    /**
     * Regression test for https://github.com/cartalyst/sentry/issues/148
     *
     * @expectedException Cartalyst\Sentry\Users\WrongPasswordException
     */
    public function testFindingByCredentialsFailsForBadPassword()
    {
        $actualUser= \Model::factory(User::CLASS_NAME)->create();

        $actualUser->email = 'foo2@bar.com';
        $actualUser->password = 'test';
        $actualUser->save();
        
        $result = $this->provider->findByCredentials(array(
            'email'      => 'foo2@bar.com',
            'password' => 'unhashed_passwordval',
        ));

        $this->assertEquals($actualUser, $result);
    }

    public function testFindingByCredentials()
    {
        $actualUser= \Model::factory(User::CLASS_NAME)->create();
        $actualUser->email = 'test@test.com';
        $actualUser->password = 'test';
        $actualUser->save();

        $result = $this->provider->findByCredentials(array(
            'email' => 'test@test.com'
        ));

        $this->assertEquals($actualUser->id, $result->id);
        $this->assertEquals($actualUser->email, $result->email);
    }

    /**
     * Regression test for https://github.com/cartalyst/sentry/issues/157
     *
     * @expectedException InvalidArgumentException
     */
    public function testFindByNullActivationCode()
    {
        $this->provider = new Provider($hasher = m::mock('Cartalyst\Sentry\Hashing\HasherInterface'));
        $this->provider->findByActivationCode(null);
    }

    /**
     * Regression test for https://github.com/cartalyst/sentry/issues/157
     *
     * @expectedException InvalidArgumentException
     */
    public function testFindByEmptyActivationCode()
    {
        $this->provider = new Provider($hasher = m::mock('Cartalyst\Sentry\Hashing\HasherInterface'));
        $this->provider->findByActivationCode('');
    }

    public function testFindByActivationCode()
    {
        $user= \Model::factory(User::CLASS_NAME)->create();
        $user->id = 1;
        $user->activation_code = 'foo';
        $user->email = 'test@test.com';
        $user->password = 'test';
        $user->save();

        $this->assertEquals($user->email, $this->provider->findByActivationCode('foo')->email);
    }

    /**
     * @expectedException Cartalyst\Sentry\Users\UserNotFoundException
     */
    public function testFailedFindByActivationCode()
    {
        $this->provider->findByActivationCode('foo');
    }

    public function testFindByResetPasswordCode()
    {
        $user= \Model::factory(User::CLASS_NAME)->create();
        $user->id = 1;
        $user->reset_password_code = 'foo';
        $user->email = 'test@test.com';
        $user->password = 'test';
        $user->save();

        $this->assertEquals($user->email, $this->provider->findByResetPasswordCode('foo')->email);
    }

    /**
     * @expectedException Cartalyst\Sentry\Users\UserNotFoundException
     */
    public function testFailedFindByResetPasswordCode()
    {
        $this->provider->findByResetPasswordCode('foo');
    }

    public function testCreatingUser()
    {
        $attributes = array(
            'email'    => 'bar@foo.com',
            'password' => 'foo_bar_baz',
        );

        $user = \Model::factory(User::CLASS_NAME)->create();

        $user->hydrate($attributes);

        $createdUser = $this->provider->create($attributes);
        $this->assertEquals($user->email, $createdUser->email);
    }

    public function testGettingEmptyUserInterface()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();

        $this->assertEquals($user, $this->provider->getEmptyUser());
    }

    public function testSettingModel()
    {
        $provider = new Provider(
            $hasher = m::mock('Cartalyst\Sentry\Hashing\HasherInterface'),
            'UserModelStub1'
        );

        $this->assertInstanceOf('UserModelStub1', $provider->createModel());

        $provider->setModel('UserModelStub2');
        $this->assertInstanceOf('UserModelStub2', $provider->createModel());
    }

    public function testFindingAllUsers()
    {
        $user = $this->provider->findById(123);

        $this->assertEquals([ $user ], $this->provider->findAll());
    }

    public function testFindingAllUsersInGroup()
    {
        $user = $this->provider->findById(123);

        $group = m::mock('Cartalyst\Sentry\Groups\Paris\Group');
        $group->shouldReceive('users')->once()->andReturn([ $user ]);

        $this->assertEquals([ $user ], $this->provider->findAllInGroup($group));
    }

    public function testFindingAllUsersWithAccess()
    {
        $user1= \Model::factory(User::CLASS_NAME)->create();
        $user1->id = 1;
        $user1->email = 'test@test.com';
        $user1->password = 'test';
        $user1->permissions = $permissions = [ 'foo' => 1, 'bar' => 1 ]; // = array('foo', 'bar'));
        $user1->save();

        $user2 = $this->provider->findById(123);
        $user2->permissions = $permissions;
        $user2->password = 'test';
        $user2->save();

        $this->assertEquals($user1->id, $this->provider->findAllWithAccess([ 'foo', 'bar' ])[0]->id);
        $this->assertEquals($user2->id, $this->provider->findAllWithAccess([ 'foo', 'bar' ])[1]->id);
    }

    public function testFindingAllUsersWithAnyAccess()
    {
        $user1 = \Model::factory(User::CLASS_NAME)->create();
        $user1->id = 1;
        $user1->reset_password_code = 'foo';
        $user1->email = 'test@test.com';
        $user1->password = 'test';
        $user1->permissions = [ 'foo' => 1 ]; // = array('foo', 'bar'));
        $user1->save();

        $user2 = $this->provider->findById(123);
        $user2->permissions = [ 'foo' => 1, 'bar' => 1 ];
        $user2->password = 'test';
        $user2->save();

        $this->assertEquals($user1->id, $this->provider->findAllWithAnyAccess([ 'foo', 'bar' ])[0]->id);
        $this->assertEquals($user2->id, $this->provider->findAllWithAnyAccess([ 'foo', 'bar' ])[1]->id);
    }
}
