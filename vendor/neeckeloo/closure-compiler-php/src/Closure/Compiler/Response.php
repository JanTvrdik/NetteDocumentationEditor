<?php
/**
 * ClosureCompilerPHP
 *
 * @link      http://github.com/neeckeloo/ClosureCompilerPHP
 * @copyright Copyright (c) 2012 Nicolas Eeckeloo
 */
namespace Closure\Compiler;

use Closure\Compiler\Response\Error as ResponseError;

class Response implements ResponseInterface
{
    /**
     * @var string
     */
    protected $compiledCode;

    /**
     * @var array
     */
    protected $warnings = array();

    /**
     * @var array
     */
    protected $errors = array();

    /**
     * @var integer
     */
    protected $originalSize;

    /**
     * @var integer
     */
    protected $originalGzipSize;

    /**
     * @var integer
     */
    protected $compressedSize;

    /**
     * @var integer
     */
    protected $compressedGzipSize;

    /**
     * @var integer
     */
    protected $compileTime;

    /**
     * Sets compiled code
     *
     * @param string $code
     * @return Response
     */
    public function setCompiledCode($code)
    {
        $this->compiledCode = (string) $code;

        return $this;
    }

    /**
     * Returns compiled code
     *
     * @return string
     */
    public function getCompiledCode()
    {
        return $this->compiledCode;
    }

    /**
     * Add warning
     *
     * @param ResponseError $warning
     * @return Response
     */
    public function addWarning(ResponseError $warning)
    {
        $this->warnings[] = $warning;

        return $this;
    }

    /**
     * Returns warnings
     *
     * @return array
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Returns true if there are warnings
     *
     * @return boolean
     */
    public function hasWarnings()
    {
        return !empty($this->warnings);
    }

    /**
     * Add error
     *
     * @param ResponseError $error
     * @return Response
     */
    public function addError(ResponseError $error)
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * Returns errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Returns true if there are errors
     *
     * @return boolean
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Sets original size
     *
     * @param integer $size
     * @return Response
     */
    public function setOriginalSize($size)
    {
        $this->originalSize = (int) $size;

        return $this;
    }

    /**
     * Returns original size
     *
     * @return integer
     */
    public function getOriginalSize()
    {
        return $this->originalSize;
    }

    /**
     * Sets original gzip size
     *
     * @param integer $size
     * @return Response
     */
    public function setOriginalGzipSize($size)
    {
        $this->originalGzipSize = (int) $size;

        return $this;
    }

    /**
     * Returns compressed gzip size
     *
     * @return integer
     */
    public function getOriginalGzipSize()
    {
        return $this->originalGzipSize;
    }

    /**
     * Sets compressed size
     *
     * @param integer $size
     * @return Response
     */
    public function setCompressedSize($size)
    {
        $this->compressedSize = (int) $size;

        return $this;
    }

    /**
     * Returns compressed size
     *
     * @return integer
     */
    public function getCompressedSize()
    {
        return $this->compressedSize;
    }

    /**
     * Sets compressed gzip size
     *
     * @param integer $size
     * @return Response
     */
    public function setCompressedGzipSize($size)
    {
        $this->compressedGzipSize = (int) $size;

        return $this;
    }

    /**
     * Returns compressed gzip size
     *
     * @return integer
     */
    public function getCompressedGzipSize()
    {
        return $this->compressedGzipSize;
    }

    /**
     * Sets compile time
     *
     * @param integer $time
     * @return Response
     */
    public function setCompileTime($time)
    {
        $this->compileTime = (int) $time;

        return $this;
    }

    /**
     * Returns compile time
     *
     * @return integer
     */
    public function getCompileTime()
    {
        return $this->compileTime;
    }

    /**
     * Returns true if code has compiled
     *
     * @return boolean
     */
    public function isCompiled()
    {
        return !$this->hasErrors() && empty($this->compiledCode);
    }
}