<?php
namespace App;

use Closure;
use Nette;
use WebLoader;


class JavaScriptMinifier extends Nette\Object
{

	/**
	 * @param  string
	 * @param  WebLoader\Compiler
	 * @param  string
	 * @return string
	 */
	public function __invoke($code, WebLoader\Compiler $loader, $file = NULL)
	{
		$compiler = new Closure\RemoteCompiler();
		$compiler->addScript($code);
		$compiler->setMode(Closure\RemoteCompiler::MODE_SIMPLE_OPTIMIZATIONS);

		$response = $compiler->compile();
		return $response->getCompiledCode();
	}

}
