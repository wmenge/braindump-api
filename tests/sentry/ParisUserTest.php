<?php namespace Cartalyst\Sentry\Tests;

require_once(__DIR__ . '../../../model/Sentry/Paris/User.php');

use Mockery as m;
use Cartalyst\Sentry\Users\Paris\User as User;

/***
 * Based on Eloquent tests of https://github.com/cartalyst/sentry/
 */
class ParisUserTest extends \Braindump\Api\Test\Integration\AbstractDbTest
{
    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__) . '/files/users-seed.xml');
    }
    
    public function testUserIdCallsKey()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->id = 'foo';
        $this->assertEquals('foo', $user->getId());
    }

    public function testUserLoginCallsLoginAttribute()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->email = 'foo@bar.com';
        $this->assertEquals('foo@bar.com', $user->getLogin());
    }

    public function testUserLoginNameCallsLoginName()
    {
        $user = m::mock('Cartalyst\Sentry\Users\Paris\User[getLoginName]');
        $user->shouldReceive('getLoginName')->once()->andReturn('foo');
        $this->assertEquals('foo', $user->getLoginName());
    }

    public function testUserPasswordCallsPasswordAttribute()
    {
        $hasher = $this->getMock('Cartalyst\Sentry\Hashing\HasherInterface', array('hash', 'checkhash'));

        $hasher->method('hash')
               ->with($this->equalTo('unhashed_password_here'))->willReturn('hashed_password_here');

        User::setHasher($hasher);

        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->password = 'unhashed_password_here';

        $this->assertEquals('hashed_password_here', $user->getPassword());
    }

    public function testGettingGroups()
    {
        $pivot = m::mock('StdClass');
        $pivot->shouldReceive('find_many')->once()->andReturn('foo');

        $user  = m::mock('Cartalyst\Sentry\Users\Paris\User[groups]');
        $user->shouldReceive('groups')->once()->andReturn($pivot);

        $this->assertEquals('foo', $user->getGroups());
    }

    public function testInGroup()
    {
        $group1 = m::mock('Cartalyst\Sentry\Groups\GroupInterface');
        $group1->shouldReceive('getId')->once()->andReturn(123);

        $group2 = m::mock('Cartalyst\Sentry\Groups\GroupInterface');
        $group2->shouldReceive('getId')->once()->andReturn(124);

        $user = m::mock('Cartalyst\Sentry\Users\Paris\User[getGroups]');
        $user->shouldReceive('getGroups')->once()->andReturn(array($group2));

        $this->assertFalse($user->inGroup($group1));
    }

    public function testAddingToGroupChecksIfAlreadyInThatGroup()
    {
        $group = m::mock('Cartalyst\Sentry\Groups\GroupInterface');
        $user  = m::mock('Cartalyst\Sentry\Users\Paris\User[inGroup,groups]');
        $user->shouldReceive('inGroup')->with($group)->once()->andReturn(true);
        $user->shouldReceive('groups')->never();

        $user->addGroup($group);
    }

    public function testAddingGroupAttachesToRelationship()
    {
        $group = m::mock('Cartalyst\Sentry\Groups\GroupInterface');

        $relationship = m::mock('StdClass');
        $relationship->shouldReceive('attach')->with($group)->once();

        $user  = m::mock('Cartalyst\Sentry\Users\Paris\User[inGroup,groups,invalidateMergedPermissionsCache,invalidateUserGroupsCache]');
        $user->shouldReceive('inGroup')->once()->andReturn(false);
        $user->shouldReceive('groups')->once()->andReturn($relationship);
        $user->shouldReceive('invalidateUserGroupsCache')->once();
        $user->shouldReceive('invalidateMergedPermissionsCache')->once();

        $this->assertTrue($user->addGroup($group));
    }

    public function testRemovingFromGroupDetachesRelationship()
    {
        $group = m::mock('Cartalyst\Sentry\Groups\GroupInterface');

        $relationship = m::mock('StdClass');
        $relationship->shouldReceive('detach')->with($group)->once();

        $user  = m::mock('Cartalyst\Sentry\Users\Paris\User[inGroup,groups,invalidateMergedPermissionsCache,invalidateUserGroupsCache]');
        $user->shouldReceive('inGroup')->once()->andReturn(true);
        $user->shouldReceive('groups')->once()->andReturn($relationship);
        $user->shouldReceive('invalidateUserGroupsCache')->once();
        $user->shouldReceive('invalidateMergedPermissionsCache')->once();

        $this->assertTrue($user->removeGroup($group));
    }

    public function testMergedPermissions()
    {
        $group1 = m::mock('Cartalyst\Sentry\Groups\GroupInterface');
        $group1->shouldReceive('getPermissions')->once()->andReturn(array(
            'foo' => 1,
            'bar' => 1,
            'baz' => 1,
        ));

        $group2 = m::mock('Cartalyst\Sentry\Groups\GroupInterface');
        $group2->shouldReceive('getPermissions')->once()->andReturn(array(
            'qux' => 1,
        ));

        $user = m::mock('Cartalyst\Sentry\Users\Paris\User[getGroups,getPermissions]');
        $user->shouldReceive('getGroups')->once()->andReturn(array($group1, $group2));
        $user->shouldReceive('getPermissions')->once()->andReturn(array(
            'corge' => 1,
            'foo'   => -1,
            'baz'   => -1,
        ));

        $expected = array(
            'foo'   => -1,
            'bar'   => 1,
            'baz'   => -1,
            'qux'   => 1,
            'corge' => 1,
        );

        $this->assertEquals($expected, $user->getMergedPermissions());
    }

    public function testSuperUserHasAccessToEverything()
    {
        $user  = m::mock('Cartalyst\Sentry\Users\Paris\User[isSuperUser]');
        $user->shouldReceive('isSuperUser')->once()->andReturn(true);

        $this->assertTrue($user->hasAccess('bar'));
    }

    public function testHasAccess()
    {
        $user = m::mock('Cartalyst\Sentry\Users\Paris\User[isSuperUser,getMergedPermissions]');
        $user->shouldReceive('isSuperUser')->twice()->andReturn(false);
        $user->shouldReceive('getMergedPermissions')->twice()->andReturn(array(
            'foo' => -1,
            'bar' => 1,
            'baz' => 1,
        ));

        $this->assertTrue($user->hasAccess('bar'));
        $this->assertFalse($user->hasAccess('foo'));
    }

    public function testHasAccessWithMultipleProperties()
    {
        $user = m::mock('Cartalyst\Sentry\Users\Paris\User[isSuperUser,getMergedPermissions]');
        $user->shouldReceive('isSuperUser')->twice()->andReturn(false);
        $user->shouldReceive('getMergedPermissions')->twice()->andReturn(array(
            'foo' => -1,
            'bar' => 1,
            'baz' => 1,
        ));

        $this->assertTrue($user->hasAccess(array('bar', 'baz')));
        $this->assertFalse($user->hasAccess(array('foo', 'bar', 'baz')));
    }

    /**
     * Feature test for https://github.com/cartalyst/sentry/issues/123
     */
    public function testWildcardPermissionsCheck()
    {
        $user = m::mock('Cartalyst\Sentry\Users\Paris\User[isSuperUser,getMergedPermissions]');
        $user->shouldReceive('isSuperUser')->atLeast(1)->andReturn(false);
        $user->shouldReceive('getMergedPermissions')->atLeast(1)->andReturn(array(
            'users.edit' => 1,
            'users.delete' => 1,
        ));

        $this->assertFalse($user->hasAccess('users'));
        $this->assertTrue($user->hasAccess('users.*'));
    }

    /**
     * Feature test for https://github.com/cartalyst/sentry/pull/131
     */
    public function testWildcardPermissionsSetting()
    {
        $user = m::mock('Cartalyst\Sentry\Users\Paris\User[isSuperUser,getMergedPermissions]');
        $user->shouldReceive('isSuperUser')->atLeast(1)->andReturn(false);
        $user->shouldReceive('getMergedPermissions')->atLeast(1)->andReturn(array(
            'users.*' => 1,
        ));

        $this->assertFalse($user->hasAccess('users'));
        $this->assertTrue($user->hasAccess('users.edit'));
        $this->assertTrue($user->hasAccess('users.delete'));
    }


    public function testAnyPermissions()
    {
        $user = m::mock('Cartalyst\Sentry\Users\Paris\User[isSuperUser,getMergedPermissions]');
        $user->shouldReceive('isSuperUser')->once()->andReturn(false);
        $user->shouldReceive('getMergedPermissions')->once()->andReturn(array(
            'foo' => -1,
            'baz' => 1,
        ));

        $this->assertTrue($user->hasAccess(array('foo', 'baz'), false));
    }

    public function testAnyPermissionsWithInvalidPermissions()
    {
        $user = m::mock('Cartalyst\Sentry\Users\Paris\User[isSuperUser,getMergedPermissions]');
        $user->shouldReceive('isSuperUser')->once()->andReturn(false);
        $user->shouldReceive('getMergedPermissions')->once()->andReturn(array(
            'foo' => -1,
            'baz' => 1,
        ));

        $this->assertFalse($user->hasAccess(array('foo', 'bar'), false));
    }

    public function testHasAnyAccess()
    {
        $user = m::mock('Cartalyst\Sentry\Users\Paris\User[hasAccess]');
        $user->shouldReceive('hasAccess')->with(array('foo', 'bar'), false)->once()->andReturn(true);

        $this->assertTrue($user->hasAnyAccess(array('foo', 'bar')));
    }

    /**
     * Regression test for https://github.com/cartalyst/sentry/issues/103
     */
    public function testSettingPermissionsWhenPermissionsAreStrings()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();

        $user->permissions = array(
            'superuser' => '1',
            'admin'    => '1',
            'foo'      => '0',
            'bar'      => '-1',
        );

        $expected = array(
            'superuser' => 1,
            'admin'     => 1,
            'bar'       => -1,
        );

        $this->assertEquals($expected, $user->permissions);
    }

    /**
     * Regression test for https://github.com/cartalyst/sentry/issues/103
     */
    public function testSettingPermissionsWhenAllPermissionsAreZero()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();

        $user->permissions = array(
            'superuser' => 0,
            'admin'     => 0,
        );

        $this->assertEquals(array(), $user->permissions);
    }

    /**
     * @expectedException Cartalyst\Sentry\Users\LoginRequiredException
     */
    public function testValidationThrowsLoginExceptionIfNoneGiven()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->validate();
    }

    /**
     * @expectedException Cartalyst\Sentry\Users\PasswordRequiredException
     */
    public function testValidationThrowsPasswordExceptionIfNoneGiven()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->email = 'foo';
        $user->validate();
    }

    /**
     * @expectedException Cartalyst\Sentry\Users\UserExistsException
     */
    public function testValidationFailsWhenUserAlreadyExists()
    {
        User::setHasher($hasher = m::mock('\Cartalyst\Sentry\Hashing\HasherInterface'));
        $hasher->shouldReceive('hash')->with('bazbat')->once()->andReturn('hashed_bazbat');

        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->email = 'foo@bar.com';
        $user->password = 'bazbat';

        $user->validate();
    }

    /**
     * @expectedException Cartalyst\Sentry\Users\UserExistsException
     */
    public function testValidationFailsWhenUserAlreadyExistsOnExistent()
    {
        User::setHasher($hasher = m::mock('\Cartalyst\Sentry\Hashing\HasherInterface'));
        $hasher->shouldReceive('hash')->with('bazbat')->once()->andReturn('hashed_bazbat');

        $user = \Model::factory(User::CLASS_NAME)->create();

        $user->id = 124;
        $user->email = 'foo@bar.com';
        $user->password = 'bazbat';

        $user->validate();
    }

    public function testValidationDoesNotThrowAnExceptionIfPersistedUserIsThisUser()
    {
        User::setHasher($hasher = m::mock('\Cartalyst\Sentry\Hashing\HasherInterface'));
        $hasher->shouldReceive('hash')->with('bazbat')->once()->andReturn('hashed_bazbat');

        $user = \Model::factory(User::CLASS_NAME)->create();

        $user->id = 123;
        $user->email = 'foo@bar.com';
        $user->password = 'bazbat';

        $this->assertTrue($user->validate());
    }

    public function testClearResetPassword()
    {
        User::setHasher($hasher = m::mock('\Cartalyst\Sentry\Hashing\HasherInterface'));
        $hasher->shouldReceive('hash')->with('test')->once()->andReturn('hashed_bazbat');

        $user = \Model::factory(User::CLASS_NAME)->create();

        $user->email = 'foo2@bar.com';
        $user->password = 'test';
        $user->reset_password_code = 'foo_bar_baz';

        $user->clearResetPassword();
        $this->assertNull($user->reset_password_code);
    }

    public function testHasherSettingAndGetting()
    {
        User::unsetHasher();
        $this->assertNull(User::getHasher());
        User::setHasher($hasher = m::mock('Cartalyst\Sentry\Hashing\HasherInterface'));
        $this->assertEquals($hasher, User::getHasher());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testHasherThrowsExceptionIfNotSet()
    {
        User::unsetHasher();
        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->checkHash('foo', 'bar');
    }

    public function testRandomStrings()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();
        $last = '';

        for ($i = 0; $i < 500; $i++) {
            $now = $user->getRandomString();

            if ($now === $last) {
                throw new \UnexpectedValueException("Two random strings are the same, [$now], [$last].");
            }

            $last = $now;
        }
    }

    public function testGetPersistCode()
    {
        $randomString = 'random_string_here';
        $hashedRandomString = 'hashed_random_string_here';

        User::setHasher($hasher = m::mock('\Cartalyst\Sentry\Hashing\HasherInterface'));
        $hasher->shouldReceive('hash')->andReturn($hashedRandomString);

        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->email = 'foo2@bar.com';
        $user->password = 'random_string_here';

        $this->assertNull($user->persist_code);

        $persistCode = $user->getPersistCode();
        $this->assertEquals($hashedRandomString, $persistCode);
        $this->assertEquals($hashedRandomString, $user->persist_code);
    }

    public function testCheckingPersistCode()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();

        // Create a new hash
        User::setHasher($hasher = m::mock('\Cartalyst\Sentry\Hashing\HasherInterface'));
        $hasher->shouldReceive('hash')->andReturn('hashed_reset_code');

        $user->persist_code = 'reset_code';

        // Check the hash
        $this->assertTrue($user->checkPersistCode('hashed_reset_code'));
        $this->assertFalse($user->checkPersistCode('not_the_codeed_reset_code'));
    }

    public function testGetActivationCode()
    {
        User::setHasher($hasher = m::mock('\Cartalyst\Sentry\Hashing\HasherInterface'));
        $hasher->shouldReceive('hash')->andReturn('hashed_bazbat');

        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->email = 'foo2@bar.com';
        $user->password = 'bazbat';

        $this->assertNull($user->activation_code);
        
        $activationCode = $user->getActivationCode();
        $this->assertNotNull($activationCode);
    }

    public function testGetResetPasswordCode()
    {
        User::setHasher($hasher = m::mock('\Cartalyst\Sentry\Hashing\HasherInterface'));
        $hasher->shouldReceive('hash')->andReturn('hashed_bazbat');

        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->email = 'foo2@bar.com';
        $user->password = 'bazbat';

        $this->assertNull($user->reset_password_code);

        $resetCode = $user->getResetPasswordCode();
        $this->assertNotNull($resetCode);
    }

    /**
     * @expectedException Cartalyst\Sentry\Users\UserAlreadyActivatedException
     */
    public function testUserIsNotActivatedTwice()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->email = 'foo2@bar.com';
        $user->password = 'bazbat';
        $user->activated = true;

        $user->attemptActivation('not_needed');
    }

    public function testUserActivation()
    {
        User::setHasher($hasher = m::mock('\Cartalyst\Sentry\Hashing\HasherInterface'));
        $hasher->shouldReceive('hash')->andReturn('hashed_bazbat');

        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->email = 'foo2@bar.com';
        $user->password = 'bazbat';

        $user->activation_code = 'activation_code';

        $this->assertNull($user->activated_at);
        $this->assertTrue($user->attemptActivation('activation_code'));
        $this->assertNull($user->activation_code);
        $this->assertTrue($user->activated);
        $this->assertInstanceOf('DateTime', $user->activated_at);
    }

    // todo: check error
    public function testCheckingPassword()
    {
        User::setHasher($hasher = m::mock('\Cartalyst\Sentry\Hashing\HasherInterface'));
        $hasher->shouldReceive('hash')->andReturn('hashed_bazbat');
        $hasher->shouldReceive('checkhash')->andReturn(true);

        $user = \Model::factory(User::CLASS_NAME)->create();

        $this->assertTrue($user->checkPassword('password'));
    }

    public function testCheckingResetPasswordCode()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();
        
        // Check the hash
        $user->reset_password_code = 'reset_code';
        $this->assertTrue($user->checkResetPasswordCode('reset_code'));
        $this->assertFalse($user->checkResetPasswordCode('not_the_reset_code'));
    }

    public function testResettingPassword()
    {
        User::setHasher($hasher = m::mock('\Cartalyst\Sentry\Hashing\HasherInterface'));
        $hasher->shouldReceive('hash')->andReturn('hashed_new_password');
        $hasher->shouldReceive('checkhash')->andReturn(true);

        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->reset_password_code = 'reset_code';
        $user->email = 'foo2@bar.com';
        $user->password = 'bazbat';

        $this->assertTrue($user->attemptResetPassword('reset_code', 'new_password'));
        $this->assertNull($user->reset_password_code);
        $this->assertEquals('hashed_new_password', $user->getPassword());
    }

    public function testPermissionsAreMergedAndRemovedProperly()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();

        $user->permissions = array(
            'foo' => 1,
            'bar' => 1,
        );

        $user->permissions = array(
            'baz' => 1,
            'qux' => 1,
            'foo' => 0,
        );

        $expected = array(
            'bar' => 1,
            'baz' => 1,
            'qux' => 1,
        );

        $this->assertEquals($expected, $user->permissions);
    }


    public function testPermissionsWithArrayCastingAndJsonCasting()
    {
        $user = \Model::factory(User::CLASS_NAME)->create();

        $user->email = 'foo@bar.com';
        $user->permissions = array(
            'foo' => 1,
            'bar' => -1,
            'baz' => 1,
        );

        $expected = array(
            'email' => 'foo@bar.com',
            'permissions' => array(
                'foo' => 1,
                'bar' => -1,
                'baz' => 1,
            ),
            //'activated' => false,
            //'login' => 'email'
        );
        $this->assertEquals($expected, $user->toArray());
        $expected = json_encode($expected);
        //$this->assertEquals($expected, json_encode($user->as_array()));
    }

    public function testDeletingUserDetachesAllGroupRelationships()
    {
        //$relationship = m::mock('StdClass');
        //$relationship->shouldReceive('detach')->once();

        //$user = m::mock('Cartalyst\Sentry\Users\Paris\User[groups]');
        //$user->shouldReceive('groups')->once()->andReturn($relationship);

        //$user->delete();
    }

    public function testSettingLoginAttribute()
    {
        $originalAttribute = User::getLoginAttributeName();
        User::setLoginAttributeName('foo');
        $this->assertEquals('foo', User::getLoginAttributeName());
        user::setLoginAttributeName($originalAttribute);
    }

    public function testRecordingLogin()
    {
        $hasher = $this->getMock('Cartalyst\Sentry\Hashing\HasherInterface', array('hash', 'checkhash'));
        $hasher->method('hash')->willReturn('hashed_bazbat');
        User::setHasher($hasher);

        $user = \Model::factory(User::CLASS_NAME)->create();
        $user->email = 'foo2@bar.com';
        $user->password = 'bazbat';

        $user->recordLogin();
        $this->assertInstanceOf('DateTime', $user->last_login);
    }

}
