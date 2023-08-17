<?php namespace Cartalyst\Sentry\Tests;

use Braindump\Api\Model\Sentry\Paris\Group as Group;
use Braindump\Api\Model\Sentry\Paris\GroupProvider;
use \PHPUnit\Framework\TestCase;

/***
 * Integration tests of Paris interface of Sentry authentication moduel.
 * Based on Eloquent tests of https://github.com/cartalyst/sentry/
 */
class ParisGroupProviderTest extends \Braindump\Api\Test\Integration\AbstractDbTest
{

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__) . '/files/groups-seed.xml');
    }

    public function testFindingById()
    {
        $provider = new GroupProvider();
        $this->assertEquals('foo', $provider->findById(123)->name);
    }

    /**
     * @expectedException Cartalyst\Sentry\Groups\GroupNotFoundException
     */
    public function testFailedFindingByIdThrowsExceptionIfNotFound()
    {
        $provider = new GroupProvider();
        $provider->findById(1);
    }

    public function testFindingByName()
    {
        $provider = new GroupProvider();
        $this->assertEquals('foo', $provider->findByName('foo')->name);
    }

    /**
     * @expectedException Cartalyst\Sentry\Groups\GroupNotFoundException
     */
    public function testFailedFindingByNameThrowsExceptionIfNotFound()
    {
        $provider = new GroupProvider();
        
        $provider->findByName('bar');
    }

    public function testFindingAll()
    {
        $provider = new GroupProvider();

        $groups = [ $provider->findById(123) ];

        $this->assertEquals($groups, $provider->findAll());
    }

    public function testCreatingGroup()
    {
        $provider = new GroupProvider();
        
        $compareGroup = \Model::factory(Group::class)->create();
        $compareGroup->name = 'bar';

        
        $attributes = array(
            'name' => 'bar',
        );

        $this->assertEquals($compareGroup->name, $provider->create($attributes)->name);
    }
}
