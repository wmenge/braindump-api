<?php namespace Cartalyst\Sentry\Tests;

use Mockery as m;
use Braindump\Api\Model\Sentry\Paris\Throttle as Throttle;
use Braindump\Api\Model\Sentry\Paris\ExtendedDateTime as ExtendedDateTime;
use DateTime;
use \PHPUnit\Framework\TestCase;

/***
 * Based on Eloquent tests of https://github.com/cartalyst/sentry/
 */
class ParisThrottleTest extends \Braindump\Api\Test\Integration\AbstractDbTest
{
    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__) . '/files/users-seed.xml');
    }

    public function testGettingUserReturnsUserObject()
    {
        $user = m::mock('StdClass');
        $user->shouldReceive('find_one')->once()->andReturn('foo');

        $throttle = m::mock('Braindump\Api\Model\Sentry\Paris\Throttle[user]');
        $throttle->shouldReceive('user')->once()->andReturn($user);

        $this->assertEquals('foo', $throttle->getUser());
    }

    public function testAttemptLimits()
    {
        Throttle::setAttemptLimit(15);
        $this->assertEquals(15, Throttle::getAttemptLimit());
    }

    public function testGettingLoginAttemptsWhenNoAttemptHasBeenMadeBefore()
    {
        $throttle = \Model::factory(Throttle::class)->create();
        
        $this->assertEquals(0, $throttle->getLoginAttempts());
        $throttle->attempts = 1;
        $this->assertEquals(1, $throttle->getLoginAttempts());
    }

    public function testGettingLoginAttemptsResetsIfSuspensionTimeHasPassedSinceLastAttempt()
    {
        $throttle = \Model::factory(Throttle::class)->create();
        $throttle->user_id = 123;

        // Let's simulate that the suspension time
        // is 11 minutes however the last attempt was
        // 10 minutes ago, we'll not reset the attempts
        Throttle::setSuspensionTime(11);
        $lastAttemptAt = new ExtendedDateTime;
        $lastAttemptAt->modify('-10 minutes');

        $throttle->last_attempt_at = $lastAttemptAt;//->format('Y-m-d H:i:s');
        $throttle->attempts = 3;
        $this->assertEquals(3, $throttle->getLoginAttempts());

        // Suspension time is 9 minutes now,
        // our attempts shall be reset
        Throttle::setSuspensionTime(9);
        $this->assertEquals(0, $throttle->getLoginAttempts());
    }

    public function testSuspend()
    {
        $throttle = \Model::factory(Throttle::class)->create();
        $throttle->user_id = 123;
        
        $this->assertNull($throttle->suspended_at);
        $throttle->suspend();

        $this->assertNotNull($throttle->suspended_at);
        $this->assertTrue($throttle->suspended);
    }

    public function testUnsuspend()
    {
        $throttle = \Model::factory(Throttle::class)->create();
        $throttle->user_id = 123;
        
        $lastAttemptAt = new DateTime;
        $suspendedAt   = new DateTime;

        $throttle->attempts        = 3;
        $throttle->last_attempt_at = $lastAttemptAt;
        $throttle->suspended       = true;
        $throttle->suspended_at    = $suspendedAt;

        $throttle->unsuspend();

        $this->assertEquals(0, $throttle->attempts);
        $this->assertNull($throttle->last_attempt_at);
        $this->assertFalse($throttle->suspended);
        $this->assertNull($throttle->suspended_at);
    }
}
