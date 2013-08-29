<?php
/**
 * ClosureCompilerPHP
 * 
 * @link      http://github.com/neeckeloo/ClosureCompilerPHP
 * @copyright Copyright (c) 2012 Nicolas Eeckeloo
 */
namespace Closure\Compiler;

use Closure\Compiler\Response\Error as CompilerResponseError;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Response
     */
    protected $response;
    
    public function setUp()
    {
        $this->response = new Response();
    }

    public function testSetCompiledCode()
    {
        $this->response->setCompiledCode('foo');
        $this->assertEquals('foo', $this->response->getCompiledCode());
    }

    public function testAddWarnings()
    {
        for ($i = 0; $i < 3; $i++) {
            $warning = new CompilerResponseError('foo', array());
            $this->response->addWarning($warning);
        }

        $this->assertCount(3, $this->response->getWarnings());
    }

    public function testHasWarnings()
    {
        $this->assertFalse($this->response->hasWarnings());

        $warning = new CompilerResponseError('foo', array());
        $this->response->addWarning($warning);

        $this->assertTrue($this->response->hasWarnings());
    }

    public function testAddErrors()
    {
        for ($i = 0; $i < 3; $i++) {
            $error = new CompilerResponseError('foo', array());
            $this->response->addError($error);
        }

        $this->assertCount(3, $this->response->getErrors());
    }

    public function testHasErrors()
    {
        $this->assertFalse($this->response->hasErrors());

        $error = new CompilerResponseError('foo', array());
        $this->response->addError($error);

        $this->assertTrue($this->response->hasErrors());
    }

    public function testSetOriginalSize()
    {
        $this->response->setOriginalSize(123);
        $this->assertEquals(123, $this->response->getOriginalSize());
    }

    public function testSetOriginalGzipSize()
    {
        $this->response->setOriginalGzipSize(123);
        $this->assertEquals(123, $this->response->getOriginalGzipSize());
    }

    public function testSetCompressedSize()
    {
        $this->response->setCompressedSize(123);
        $this->assertEquals(123, $this->response->getCompressedSize());
    }

    public function testSetCompressedGzipSize()
    {
        $this->response->setCompressedGzipSize(123);
        $this->assertEquals(123, $this->response->getCompressedGzipSize());
    }

    public function testSetCompileTime()
    {
        $this->response->setCompileTime(123);
        $this->assertEquals(123, $this->response->getCompileTime());
    }

    public function testIsCompiled()
    {
        $this->assertTrue($this->response->isCompiled());

        $error = new CompilerResponseError('foo', array());
        $this->response->addError($error);

        $this->assertFalse($this->response->isCompiled());
    }
}