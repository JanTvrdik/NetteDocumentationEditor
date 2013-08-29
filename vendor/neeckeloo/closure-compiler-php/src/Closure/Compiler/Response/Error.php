<?php
/**
 * ClosureCompilerPHP
 *
 * @link      http://github.com/neeckeloo/ClosureCompilerPHP
 * @copyright Copyright (c) 2012 Nicolas Eeckeloo
 */
namespace Closure\Compiler\Response;

class Error
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @var array
     */
    protected $attributes = array();

    /**
     * @var array
     */
    protected $availableAttributes = array(
        'type', 'file', 'line', 'char', 'code'
    );

    /**
     * Constructor
     * 
     * @param string $message
     * @param array $attribs
     */
    public function __construct($message, array $attribs)
    {
        $this->message = (string) $message;

        foreach ($attribs as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * Sets an attribute
     * 
     * @param string $name
     * @param string|integer $value
     */
    protected function setAttribute($name, $value)
    {
        if (!in_array($name, $this->availableAttributes)) {
            return;
        }

        if (is_numeric($value)) {
            $this->attributes[$name] = (int) $value;
        } else {
            $this->attributes[$name] = (string) $value;
        }
    }

    /**
     * Returns an attribute
     *
     * @param string $name
     * @return string|integer
     */
    protected function getAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     * Returns error message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns error type
     *
     * @return string
     */
    public function getType()
    {
        return $this->getAttribute('type');
    }

    /**
     * Returns error file
     *
     * @return string
     */
    public function getFile()
    {
        return $this->getAttribute('file');
    }

    /**
     * Returns error line
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->getAttribute('line');
    }

    /**
     * Returns error char
     *
     * @return integer
     */
    public function getChar()
    {
        return $this->getAttribute('char');
    }

    /**
     * Returns error code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getAttribute('code');
    }
}