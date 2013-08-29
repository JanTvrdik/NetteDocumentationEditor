<?php

namespace WebLoader\Filter;

/**
 * Convert LESS to CSS
 *
 * Add to composer.json "https://github.com/Mordred/less.php"
 *
 * @author Mgr. Martin Jantošovič <martin.jantosovic@freya.sk>
 */
class LessFilter extends \Nette\Object {

	const RE_STRING = '\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*"';

	private $lc;

	/**
	 * @return \lessc
	 */
	private function getLessC()
	{
		// lazy loading
		if (empty($this->lc)) {
			$this->lc = new \lessc();
		}

		return $this->lc;
	}

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
		// Iba na LESS subory
		if (strtolower($info['extension']) != 'less') {
			return $code;
		}

		$dir = dirname($file);
		$dependencies = [];
		foreach (\Nette\Utils\Strings::matchAll($code, '/@import ('.self::RE_STRING.');/') as $match) {
			$dependedFile = $dir . '/' . substr($match[1], 1, strlen($match[1]) - 2);
			if (is_file($dependedFile))
				$dependencies[] = $dependedFile;
		}
		if ($dependencies)
			$loader->setDependedFiles($file, $dependencies);

		$lessc = $this->getLessC();

		$lessc->importDir = [ $info['dirname'], '' ];
		return $lessc->parse($code);
	}

}
