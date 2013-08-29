<?php
/**
 * ClosureCompilerPHP
 *
 * @link      http://github.com/neeckeloo/ClosureCompilerPHP
 * @copyright Copyright (c) 2012 Nicolas Eeckeloo
 */
namespace Closure\Compiler\Response;

class ErrorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Error
     */
    protected $error;

    public function setUp()
    {
        $this->error = new Error('error_message', array(
            'type' => 'error_type',
            'file' => 'error_file',
            'line' => 'error_line',
            'char' => 'error_char',
            'code' => 'error_code',
        ));
    }

    public function testGetMessage()
    {
        $this->assertEquals('error_message', $this->error->getMessage());
    }

    public function testGetAttributes()
    {
        $this->assertEquals('error_type', $this->error->getType());
        $this->assertEquals('error_file', $this->error->getFile());
        $this->assertEquals('error_line', $this->error->getLine());
        $this->assertEquals('error_char', $this->error->getChar());
        $this->assertEquals('error_code', $this->error->getCode());
    }
}