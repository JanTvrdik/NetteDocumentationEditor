<?php
/**
 * ClosureCompilerPHP
 *
 * @link      http://github.com/neeckeloo/ClosureCompilerPHP
 * @copyright Copyright (c) 2012 Nicolas Eeckeloo
 */
namespace Closure\Compiler;

class FormattingOptions
{
    /**
     * Pretty print enabled option
     *
     * @var boolean
     */
    protected $prettyPrintEnabled = false;

    /**
     * Print input delimiter enabled option
     *
     * @var boolean
     */
    protected $printInputDelimiterEnabled = false;

    /**
     * Enable/disable print input delimiter
     *
     * @param  boolean $enabled
     * @return Options
     */
    public function setPrettyPrintEnabled($enabled)
    {
        $this->prettyPrintEnabled = (bool) $enabled;

        return $this;
    }

    /**
     * Returns true if print input delimiter is enabled
     *
     * @return boolean
     */
    public function getPrettyPrintEnabled()
    {
        return $this->prettyPrintEnabled;
    }

    /**
     * Enable/disable print input delimiter enabled
     *
     * @param  boolean $enabled
     * @return Options
     */
    public function setPrintInputDelimiterEnabled($enabled)
    {
        $this->printInputDelimiterEnabled = (bool) $enabled;

        return $this;
    }

    /**
     * Returns true if print input delimiter enabled
     *
     * @return boolean
     */
    public function getPrintInputDelimiterEnabled()
    {
        return $this->printInputDelimiterEnabled;
    }
}