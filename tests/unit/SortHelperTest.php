<?php namespace Braindump\Api\Test\Unit;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/SortHelper.php';
require_once __DIR__ . '/MockORMHelper.php';

use Braindump\Api\Lib\SortHelper as SortHelper;

class SortHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $orm;

    protected function setUp()
    {
        $this->orm = $this->getMock('\MockORMFacade', ['order_by_asc', 'order_by_desc']);
        $this->orm->method('order_by_asc')->willReturn($this->orm);
        $this->orm->method('order_by_desc')->willReturn($this->orm);
    }

    /**
     *
     * Test parsing of 'title,-modification style sort string'
     *
     * @dataProvider expressionProvider
     */
    public function testAddSortExpression($expr, $ascCount, $descCount, $fields)
    {
        $this->orm->expects($this->exactly($ascCount))->method('order_by_asc');
        $this->orm->expects($this->exactly($descCount))->method('order_by_desc');
        
        $query = SortHelper::addSortExpression($this->orm, $expr);
    }

    public function expressionProvider()
    {
        return [

          // Basic testcases with a few fields
          ['title'           , 1, 0, ['title']],
          ['title,modified'  , 2, 0, ['title','modified']],
          ['-title'          , 0, 1, ['title']],
          ['title,-modified' , 1, 1, ['title','modified']],
          ['-title,-modified', 0, 2, ['title','modified']],

          // Bad user input (leading, trailing spaces, should be corrected
          ['title '           , 1, 0, ['title']],
          [' title, modified ', 2, 0, ['title','modified']],
          ['- title'          , 0, 1, ['title']],
          ['title, - modified', 1, 1, ['title','modified']],

          // No input, should lead to empty output
          ['', 0, 0, []],
        ];
    }
}
