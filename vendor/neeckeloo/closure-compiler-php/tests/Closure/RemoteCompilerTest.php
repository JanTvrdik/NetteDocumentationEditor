<?php
/**
 * ClosureCompilerPHP
 * 
 * @link      http://github.com/neeckeloo/ClosureCompilerPHP
 * @copyright Copyright (c) 2012 Nicolas Eeckeloo
 */
namespace Closure;

class RemoteCompilerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RemoteCompiler
     */
    protected $compiler;
    
    public function setUp()
    {
        $this->compiler = new RemoteCompiler();
    }

    public function testSetRequestHandler()
    {
        $this->assertInstanceOf(
            '\Zend\Http\Client',
            $this->compiler->getRequestHandler()
        );

        $this->compiler->setRequestHandler(new \Zend\Http\Client());
        $this->assertInstanceOf(
            '\Zend\Http\Client',
            $this->compiler->getRequestHandler()
        );
    }

    public function testCompile()
    {
        $xml = '<compilationResult>
          <compiledCode>var a="hello";alert(a);</compiledCode>
          <statistics>
            <originalSize>98</originalSize>
            <originalGzipSize>62</originalGzipSize>
            <compressedSize>35</compressedSize>
            <compressedGzipSize>23</compressedGzipSize>
            <compileTime>0</compileTime>
          </statistics>
        </compilationResult>';

        $httpResponse = $this->getMock('\Zend\Http\Response', array('getContent'));

        $httpResponse->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($xml));

        $requestHandler = $this->getMock('\Zend\Http\Client', array('send'));

        $requestHandler->expects($this->once())
            ->method('send')
            ->will($this->returnValue($httpResponse));

        $this->compiler->setRequestHandler($requestHandler);

        $response = $this->compiler->compile();

        $this->assertInstanceOf('Closure\Compiler\Response', $response);

        $this->assertEquals('var a="hello";alert(a);', $response->getCompiledCode());
        $this->assertTrue(is_array($response->getWarnings()));
        $this->assertTrue(is_array($response->getErrors()));
        $this->assertEquals(0, $response->getCompileTime());
        $this->assertEquals(98, $response->getOriginalSize());
        $this->assertEquals(62, $response->getOriginalGzipSize());
        $this->assertEquals(35, $response->getCompressedSize());
        $this->assertEquals(23, $response->getCompressedGzipSize());
    }
}