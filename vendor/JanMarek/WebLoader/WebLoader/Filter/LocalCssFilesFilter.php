<?php

namespace WebLoader\Filter;

/**
 * If CSS file contains a url() reference to the file which is not
 * accessible after compile, the file will be copied to right place.
 *
 * @author Martin Jantosovic <martin.jantosovic@freya.sk>
 * @license MIT
 */
class LocalCssFilesFilter {

	/** @var string Path to the document root */
	private $docRoot;

	/** @var string Path to the webtemp directory */
	private $webtemp;

	/**
	 * @param string $docRoot web document root
	 * @param string $basePath webtemp directory for compiled CSS
	 */
	public function __construct($docRoot) {
		$this->docRoot = realpath($docRoot);

		if (!is_dir($this->docRoot))
			throw new \WebLoader\InvalidArgumentException('Given document root is not directory.');
	}

	/**
	 * Copy file relative to source CSS file to the right place
	 * relative to the compiled file
	 *
	 * @param string $url url
	 * @param string $quote single or double quote
	 * @param string $cssFile absolute css file path
	 * @return string
	 */
	public function copyLocalFile($url, $quote, $cssFile)
	{
		// If URL is abolute - skip
		if (preg_match("/^([a-z]+:\/)?\//", $url)) {
			return $url;
		}

		// Remove everything all params (after ?..)
		$url = preg_replace("/\?.*/", "", $url);

		$cssFile = realpath($cssFile);

		$directory = dirname($cssFile);
		$localFile = realpath($directory . '/' . $url);

		if ($localFile === FALSE)
			// Local file does not exist
			return FALSE;

		$webtempFile = $this->webtemp . '/' . $url;
		if (realpath($webtempFile) === FALSE // File not exists
			&& strncmp($webtempFile, $this->docRoot, strlen($this->docRoot)) === 0) { // File must be inside document root
			$directory = dirname($webtempFile);
			if (!is_dir($directory)) { // Create directory if not exists
				mkdir($directory, 0755, TRUE);
			}
			copy($localFile, $webtempFile);
			return TRUE;
		} else {
			// File already exists
			return FALSE;
		}
	}

	/**
	 * Invoke filter
	 * @param string $code
	 * @param \WebLoader\Compiler $loader
	 * @param string $file
	 * @return string
	 */
	public function __invoke($code, \WebLoader\Compiler $loader, $file = null)
	{
		$this->webtemp = $loader->getOutputDir();

		// thanks to kravco
		$regexp = '~
			(?<![a-z])
			url\(                                     ## url(
				\s*                                   ##   optional whitespace
				([\'"])?                              ##   optional single/double quote
				(   (?: (?:\\\\.)+                    ##     escape sequences
					|   [^\'"\\\\,()\s]+              ##     safe characters
					|   (?(1)   (?!\1)[\'"\\\\,() \t] ##       allowed special characters
						|       ^                     ##       (none, if not quoted)
						)
					)*                                ##     (greedy match)
				)
				(?(1)\1)                              ##   optional single/double quote
				\s*                                   ##   optional whitespace
			\)                                        ## )
		~xs';

		$self = $this;

		return preg_replace_callback($regexp, function ($matches) use ($self, $file)
		{
			$self->copyLocalFile($matches[2], $matches[1], $file);
			return $matches[0];
		}, $code);
	}

}
