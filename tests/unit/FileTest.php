<?php
namespace Braindump\Api\Test\Unit;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../model/File.php';

use Braindump\Api\Model\File as File;

// Mock config
File::$config = [
        'upload_directory' => '../some/dir/',
        'mime_types' => [
            'image/png' => 'inline'
        ]
     ];

class FileTest extends \PHPUnit_Framework_TestCase
{
    protected $file;

    protected function setUp()
    {
        $this->file = File::create();
    }

    /**
     * @dataProvider isValidProvider
     */
    public function testIsValid($model, $expectedValid)
    {
        $this->assertEquals($expectedValid, File::isValid($model));
    }

    public function isValidProvider()
    {
        return [
            [null, false],
            [42, false],
            ['Invalid string', false],
            [['field' => 'an array with a string'], false],
            [(object)['some_property' => 'A string'], false],
            [(object)['logical_filename' => 'A string'], false],
            [(object)[
                'logical_filename'  => 'filename.pdf',
                'physical_filename' => 'some physical name',
                'original_filename' => 'filename.pdf',
                'mime_type'         => 'image/png',
                'hash'              => 'some hash',
                'size'              => 'invalid size'
            ], false],
            [(object)[
                'logical_filename'  => 'filename.pdf',
                'physical_filename' => 'some physical name',
                'original_filename' => 'filename.pdf',
                'mime_type'         => 'image/png',
                'hash'              => 'some hash',
                'size'              => 1
            ], true]
        ];
    }

    /**
     * @dataProvider mapProvider
     */
    public function testMap($input, $output)
    {
        $this->file->map($input);
        $this->assertEquals($output, $this->file->as_array());
    }

    public function mapProvider()
    {
        return [
            [null, []],
            [42, []],
            ['Invalid string', []],
            [['field' => 'an array with a string'], []],
            [(object)['field' => 'an obect with an incorrect property'], []],
            [(object)['title' => 42], []],
            [(object)['logical_filename' => 'filename.pdf'], []],
            // Valid object
            [(object)[
                'logical_filename'  => 'filename.pdf',
                'physical_filename' => 'some physical name',
                'original_filename' => 'filename.pdf',
                'mime_type'         => 'image/png',
                'hash'              => 'some hash',
                'size'              => 1
            ],
            [
                'logical_filename'  => 'filename.pdf',
                'physical_filename' => 'some physical name',
                'original_filename' => 'filename.pdf',
                'mime_type'         => 'image/png',
                'hash'              => 'some hash',
                'size'              => 1
            ]],
        ];
    }
}
