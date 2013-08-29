<?php
/**
 * ClosureCompilerPHP
 *
 * @link      http://github.com/neeckeloo/ClosureCompilerPHP
 * @copyright Copyright (c) 2012 Nicolas Eeckeloo
 */
namespace Closure;

use Closure\Compiler\FormattingOptions;
use Closure\Compiler\Response as CompilerResponse;
use Closure\Compiler\ResponseInterface as CompilerResponseInterface;

abstract class AbstractCompiler implements CompilerInterface
{
    const MODE_WHITESPACE_ONLY = 'WHITESPACE_ONLY';
    const MODE_SIMPLE_OPTIMIZATIONS = 'SIMPLE_OPTIMIZATIONS';
    const MODE_ADVANCED_OPTIMIZATIONS = 'ADVANCED_OPTIMIZATIONS';

    const WARNING_LEVEL_DEFAULT = 'default';
    const WARNING_LEVEL_QUIET = 'quiet';
    const WARNING_LEVEL_VERBOSE = 'verbose';

    /**
     * @var string
     */
    protected $mode = self::MODE_WHITESPACE_ONLY;

    /**
     * Available modes
     *
     * @var array
     */
    protected $availableModes = array(
        self::MODE_WHITESPACE_ONLY,
        self::MODE_SIMPLE_OPTIMIZATIONS,
        self::MODE_ADVANCED_OPTIMIZATIONS,
    );

    /**
     * @var string
     */
    protected $warningLevel = self::WARNING_LEVEL_DEFAULT;

    /**
     * Available warning levels
     *
     * @var array
     */
    protected $availableWarningLevels = array(
        self::WARNING_LEVEL_DEFAULT,
        self::WARNING_LEVEL_QUIET,
        self::WARNING_LEVEL_VERBOSE,
    );

    /**
     * @var FormattingOptions
     */
    protected $formattingOptions;

    /**
     * @var array
     */
    protected $files = array();

    /**
     * @var array
     */
    protected $scripts = array();

    /**
     * @var CompilerResponse
     */
    protected $response;

    /**
     * Sets mode
     *
     * @param string $mode
     * @return RemoteCompiler
     * @throws Exception\InvalidArgumentException
     */
    public function setMode($mode = self::MODE_WHITESPACE_ONLY)
    {
        if (!in_array($mode, $this->availableModes)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The mode "%s" is not available.',
                $mode
            ));
        }

        $this->mode = (string) $mode;

        return $this;
    }

    /**
     * Returns mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Sets warning level
     *
     * @param string $level
     * @return RemoteCompiler
     * @throws Exception\InvalidArgumentException
     */
    public function setWarningLevel($level = self::WARNING_LEVEL_DEFAULT)
    {
        if (!in_array($level, $this->availableWarningLevels)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The warning level "%s" is not available.',
                $level
            ));
        }

        $this->warningLevel = (string) $level;

        return $this;
    }

    /**
     * Returns warning level
     *
     * @return string
     */
    public function getWarningLevel()
    {
        return $this->warningLevel;
    }

    /**
     * Sets formatting options
     *
     * @param  FormattingOptions $options
     * @return AbstractCompiler
     */
    public function setFormattingOptions(FormattingOptions $options)
    {
        $this->formattingOptions = $options;

        return $this;
    }

    /**
     * Returns formatting options
     *
     * @return FormattingOptions
     */
    public function getFormattingOptions()
    {
        if (!isset($this->formattingOptions)) {
            $this->setFormattingOptions(new FormattingOptions());
        }

        return $this->formattingOptions;
    }

    /**
     * Add script
     *
     * @param string $script
     * @return AbstractCompiler
     */
    public function addScript($script)
    {
        $this->scripts[] = (string) $script;

        return $this;
    }

    /**
     * Add local javascript file
     *
     * @param string $file
     * @return AbstractCompiler
     * @throws Exception\InvalidArgumentException
     */
    public function addLocalFile($file)
    {
        if (!file_exists($file)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The file "%s" does not exists.',
                $file
            ));
        }

        $this->addScript(file_get_contents($file));

        return $this;
    }

    /**
     * Add local files contains in directory
     * 
     * @param string $directory
     * @param boolean $recursive
     * @return AbstractCompiler
     * @throws Exception\InvalidArgumentException
     */
    public function addLocalDirectory($directory, $recursive = false)
    {
        if (!file_exists($directory) || !is_dir($directory)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The directory "%s" does not exists.',
                $directory
            ));
        }

        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory)
            );
        } else {
            $iterator = new \DirectoryIterator($directory);
        }

        foreach ($iterator as $fileinfo) {
            if (!$fileinfo->isFile()) {
                continue;
            }
            
            $extension = $fileinfo->getExtension();
            if ($extension != 'js') {
                continue;
            }

            $this->addLocalFile($fileinfo->getPathname());
        }

        return $this;
    }

    /**
     * Add remote javascript file
     *
     * @param string $url
     * @return AbstractCompiler
     * @throws Exception\InvalidArgumentException
     */
    public function addRemoteFile($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The url "%s" is not valid.',
                $url
            ));
        }

        $this->files[] = (string) $url;

        return $this;
    }

    /**
     * Sets compiler response
     *
     * @param CompilerResponse $response
     * @return AbstractCompiler
     */
    public function setCompilerResponse(CompilerResponseInterface $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Returns compiler response
     *
     * @return CompilerResponse
     */
    public function getCompilerResponse()
    {
        if (!isset($this->response)) {
            $this->setCompilerResponse(new CompilerResponse());
        }

        return $this->response;
    }

    /**
     * Returns compiler params
     *
     * @return array
     */
    public function getParams()
    {
        $params = array(
            'compilation_level' => $this->getMode(),
            'output_format'     => 'xml',
            'warning_level'     => $this->getWarningLevel(),
            'output_info_1'     => 'compiled_code',
            'output_info_2'     => 'statistics',
            'output_info_3'     => 'warnings',
            'output_info_4'     => 'errors',
        );

        $formattingOptions = $this->getFormattingOptions();

        if ($formattingOptions->getPrettyPrintEnabled()) {
            $params['formatting'] = 'pretty_print';
        }

        if ($formattingOptions->getPrintInputDelimiterEnabled()) {
            $params['formatting'] = 'print_input_delimiter';
        }

        if (count($this->scripts) > 0) {
            $params['js_code'] = implode("\n\n", $this->scripts);
        }

        foreach ($this->files as $key => $file) {
            $params['code_url_' . $key] = $file;
        }

        return $params;
    }

    /**
     * Returns compile hash
     *
     * @return string
     */
    public function getHash()
    {
        return md5(
            implode('', $this->scripts)
            . implode('', $this->files)
            . $this->mode
            . $this->warningLevel
            . $this->getFormattingOptions()->getPrettyPrintEnabled()
            . $this->getFormattingOptions()->getPrintInputDelimiterEnabled()
        );
    }

    /**
     * Compile Javascript code
     *
     * @return CompilerResponse
     * @throws Exception\RuntimeException
     */
    public function compile()
    {
        throw new Exception\RuntimeException('Compile method not implemented.');
    }
}