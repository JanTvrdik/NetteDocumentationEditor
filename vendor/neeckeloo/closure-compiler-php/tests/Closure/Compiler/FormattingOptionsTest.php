<?php
/**
 * ClosureCompilerPHP
 * 
 * @link      http://github.com/neeckeloo/ClosureCompilerPHP
 * @copyright Copyright (c) 2012 Nicolas Eeckeloo
 */
namespace Closure\Compiler;

class FormattingOptionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormattingOptions
     */
    protected $options;
    
    public function setUp()
    {
        $this->options = new FormattingOptions();
    }

    public function testSetPrettyPrintEnabled()
    {
        $this->assertFalse($this->options->getPrettyPrintEnabled());

        $this->options->setPrettyPrintEnabled(true);
        $this->assertTrue($this->options->getPrettyPrintEnabled());
    }

    public function testSetPrintInputDelimiterEnabled()
    {
        $this->assertFalse($this->options->getPrintInputDelimiterEnabled());

        $this->options->setPrintInputDelimiterEnabled(true);
        $this->assertTrue($this->options->getPrintInputDelimiterEnabled());
    }
}