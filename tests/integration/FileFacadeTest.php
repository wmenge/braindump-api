<?php
/*namespace Braindump\Api\Model;

// Mocking standard time function to be able to compare DB content
function time()
{
    return 0;
}*/

namespace Braindump\Api\Test\Integration;

require_once __DIR__ . '/../../model/FileFacade.php';

class FileFacadeTest extends AbstractDbTest
{
    protected $Facade;

    protected function setUp()
    {
        parent::setUp();

        $mockApp = new \stdClass();
        $mockApp->braindumpConfig = (require( __DIR__ . '/../../config/braindump-config.php'));
        $this->Facade = new \Braindump\Api\Model\FileFacade();

        \Sentry::$id = 1;
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet()
    {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/files/file-seed.xml');
    }

    /**
     * @dataProvider getUniqueNameProvider
     */
    public function testGetUniqueName($filename, $expectedFilename)
    {
        $this->assertEquals($expectedFilename, $this->Facade->getUniqueName($filename));
    }

    public function getUniqueNameProvider()
    {
        return [
            ['unique_file.png', 'unique_file.png'],
            ['existing_file.png', 'existing_file 2.png'],
            ['multiple_existing_file.png', 'multiple_existing_file 3.png'],
            ['multiple_existing_file 2.png', 'multiple_existing_file 3.png'],
            ['multiple_existing_file 4.png', 'multiple_existing_file 4.png'],
        ];
    }
}
