<?php
namespace App;

use CssMin;
use Nette;
use WebLoader;


class CssMinifier extends Nette\Object
{

	/**
	 * @param  string
	 * @param  WebLoader\Compiler
	 * @param  string
	 * @return string
	 */
	public function __invoke($code, WebLoader\Compiler $loader, $file = NULL)
	{
		return CssMin::minify($code);
	}

}
