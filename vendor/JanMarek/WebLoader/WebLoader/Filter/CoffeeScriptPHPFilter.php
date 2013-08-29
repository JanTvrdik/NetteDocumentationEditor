<?php

namespace WebLoader\Filter;

/**
 * Convert Coffee to JS
 *
 * Add to composer.json "coffeescript/coffeescript"
 *
 * @author Mgr. Martin Jantošovič <martin.jantosovic@freya.sk>
 */
class CoffeeScriptPHPFilter extends \Nette\Object {

	/**
	 * Invoke filter
	 * @param string code
	 * @param WebLoader loader
	 * @param string file
	 * @return string
	 */
	public function __invoke($code, \WebLoader\Compiler $loader, $file = null)
	{
		$info = pathinfo($file);
		// Iba na Coffee subory
		if (strtolower($info['extension']) != 'coffee') {
			return $code;
		}

		$code = \CoffeeScript\Compiler::compile($code, ['filename' => $file ]);

		return $code;
	}

}
