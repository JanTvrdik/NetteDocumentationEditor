<?php

namespace WebLoader\Filter;

/**
 * Remove BOM Flags from files
 *
 * @author Mgr. Martin Jantošovič <martin.jantosovic@freya.sk>
 */
class BOMFilter extends \Nette\Object {

	const BOM = '\xEF\xBB\xBF';

	/**
	 * Invoke filter
	 * @param string code
	 * @param WebLoader loader
	 * @param string file
	 * @return string
	 */
	public function __invoke($code, \WebLoader\Compiler $loader, $file = null)
	{
		$regexp = '/' . self::BOM . '/';
		$code = \Nette\Utils\Strings::replace($code, $regexp);

		return $code;
	}

}
