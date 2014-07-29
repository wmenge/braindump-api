<?php
namespace Braindump\Api\Tests;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/DatabaseHelper.php';
use Braindump\Api\DatabaseHelper;

class DatabaseHelperTest extends \PHPUnit_Framework_TestCase
{
    protected $app;
    protected $helper;

    protected function setUp()
    {
        //$this->app = $this->getMock('\Slim\Slim');
        $this->app = $this->getMock('Slim');
        $this->helper = new DatabaseHelper($this->app);
    }

    /**
     *
     * Test parsing of 'title,-modification style sort string'
     *
     * @dataProvider expressionProvider
     */
    public function testParseSortExpression($expr, $expectedCount, $fields, $orders)
    {
        $expressions = $this->helper->parseSortExpression($expr);

        $this->assertCount($expectedCount, $expressions);

        for ($i = 0; $i < count($fields); $i++) {
            $expression = $expressions[$i];
            $this->assertEquals($fields[$i], $expression->field);
            $this->assertEquals($orders[$i], $expression->order);
        }
    }

    public function expressionProvider()
    {
        return array(

          // Basic testcases with a few fields
          array('title', 1, ['title'], [SORT_ASC]),
          array('title,modified', 2, ['title','modified'], [SORT_ASC, SORT_ASC]),
          array('-title', 1, ['title'], [SORT_DESC]),
          array('title,-modified', 2, ['title','modified'], [SORT_ASC, SORT_DESC]),

          // Bad user input (leading, trailing spaces, should be corrected
          array('title ', 1, ['title'], [SORT_ASC]),
          array(' title, modified ', 2, ['title','modified'], [SORT_ASC, SORT_ASC]),
          array('- title', 1, ['title'], [SORT_DESC]),
          array('title, - modified', 2, ['title','modified'], [SORT_ASC, SORT_DESC]),

          // No input, should lead to empty output
          array('', 0, [], []),
        );
    }
}
