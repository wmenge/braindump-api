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
        $this->orm = $this->createMock('\ORM', ['order_by_asc', 'order_by_desc']);
        $this->orm->method('order_by_asc')->willReturn($this->orm);
        $this->orm->method('order_by_desc')->willReturn($this->orm);
    }

    /**
     *
     * Test parsing of 'title,-modification style sort string'
     *
     * @dataProvider expressionProvider
     */
    public function testParseSortExpression($expr, $ascCount, $descCount, $fields)
    {
        $sortList = SortHelper::parseSortExpression($expr);
        $this->assertEquals($fields, $sortList);
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
          ['title'           , 1, 0, [ (object)['field' => 'title', 'order' => SORT_ASC]]],
          ['title,modified'  , 2, 0, [ (object)['field' => 'title', 'order' => SORT_ASC],
                                       (object)['field' => 'modified', 'order' => SORT_ASC]]],
          ['-title'          , 0, 1, [ (object)['field' => 'title', 'order' => SORT_DESC]]],
          ['title,-modified' , 1, 1, [ (object)['field' => 'title', 'order' => SORT_ASC],
                                       (object)['field' => 'modified', 'order' => SORT_DESC]]],
          ['-title,-modified', 0, 2, [ (object)['field' => 'title', 'order' => SORT_DESC],
                                       (object)['field' => 'modified', 'order' => SORT_DESC]]],

          // Bad user input (leading, trailing spaces, should be corrected
          ['title '           , 1, 0, [ (object)['field' => 'title', 'order' => SORT_ASC]]],
          [' title, modified ', 2, 0, [ (object)['field' => 'title', 'order' => SORT_ASC],
                                        (object)['field' => 'modified', 'order' => SORT_ASC]]],
          ['- title'          , 0, 1, [ (object)['field' => 'title', 'order' => SORT_DESC]]],
          ['title, - modified', 1, 1, [ (object)['field' => 'title', 'order' => SORT_ASC],
                                        (object)['field' => 'modified', 'order' => SORT_DESC]]],

          // No input, should lead to empty output
          ['', 0, 0, []],
        ];
    }
}
