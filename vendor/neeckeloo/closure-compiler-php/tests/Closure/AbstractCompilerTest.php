<?php
/**
 * ClosureCompilerPHP
 * 
 * @link      http://github.com/neeckeloo/ClosureCompilerPHP
 * @copyright Copyright (c) 2012 Nicolas Eeckeloo
 */
namespace Closure;

use Closure\Compiler\Response as CompilerResponse;

class AbstractCompilerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractCompiler
     */
    protected $compiler;
    
    public function setUp()
    {
        $this->compiler = $this->getMockForAbstractClass('Closure\AbstractCompiler');
    }

    public function testSetMode()
    {
        $this->assertEquals(
            AbstractCompiler::MODE_WHITESPACE_ONLY,
            $this->compiler->getMode()
        );

        $this->compiler->setMode(AbstractCompiler::MODE_ADVANCED_OPTIMIZATIONS);
        $this->assertEquals(
            AbstractCompiler::MODE_ADVANCED_OPTIMIZATIONS,
            $this->compiler->getMode()
        );
    }

    /**
     * @expectedException Closure\Exception\InvalidArgumentException
     */
    public function testSetModeWithInvalidParam()
    {
        $this->compiler->setMode('foo');
    }

    public function testSetWarningLevel()
    {
        $this->assertEquals(
            AbstractCompiler::WARNING_LEVEL_DEFAULT,
            $this->compiler->getWarningLevel()
        );

        $this->compiler->setWarningLevel(AbstractCompiler::WARNING_LEVEL_VERBOSE);
        $this->assertEquals(
            AbstractCompiler::WARNING_LEVEL_VERBOSE,
            $this->compiler->getWarningLevel()
        );
    }

    /**
     * @expectedException Closure\Exception\InvalidArgumentException
     */
    public function testSetWarningLevelWithInvalidParam()
    {
        $this->compiler->setWarningLevel('foo');
    }

    public function testSetFormattingOptions()
    {
        $this->assertInstanceOf(
            'Closure\Compiler\FormattingOptions',
            $this->compiler->getFormattingOptions()
        );

        $this->compiler->setFormattingOptions(new Compiler\FormattingOptions);
        $this->assertInstanceOf(
            'Closure\Compiler\FormattingOptions',
            $this->compiler->getFormattingOptions()
        );
    }

    public function testAddLocalFile()
    {
        $this->compiler->addLocalFile(__DIR__ . '/_files/file1.js');
    }

    /**
     * @expectedException Closure\Exception\InvalidArgumentException
     */
    public function testAddLocalFileThatNotExists()
    {
        $this->compiler->addLocalFile('foo.js');
    }

    public function testAddLocalDirectory()
    {
        $this->compiler->addLocalDirectory(__DIR__ . '/_files');
    }

    /**
     * @expectedException Closure\Exception\InvalidArgumentException
     */
    public function testAddLocalDirectoryThatNotExists()
    {
        $this->compiler->addLocalDirectory('./foo');
    }

    /**
     * @expectedException Closure\Exception\InvalidArgumentException
     */
    public function testAddLocalDirectoryWithFileParam()
    {
        $this->compiler->addLocalDirectory(__DIR__ . '/_files/file1.js');
    }

    /**
     * @expectedException Closure\Exception\InvalidArgumentException
     */
    public function testAddRemoteFileWithInvalidUrl()
    {
        $this->compiler->addRemoteFile('foo');
    }

    public function testSetCompilerResponse()
    {
        $this->assertInstanceOf(
            'Closure\Compiler\Response',
            $this->compiler->getCompilerResponse()
        );

        $this->compiler->setCompilerResponse(new CompilerResponse());
        $this->assertInstanceOf(
            'Closure\Compiler\Response',
            $this->compiler->getCompilerResponse()
        );
    }

    public function testGetParams()
    {
        $this->assertCount(7, $this->compiler->getParams());
    }

    public function testGetHash()
    {
        $hash = $this->compiler->getHash();
        
        $this->assertTrue(is_string($hash));
        $this->assertEquals(32, strlen($hash));
    }

    /**
     * @expectedException Closure\Exception\RuntimeException
     */
    public function testCompile()
    {
        $this->compiler->compile();
    }
}