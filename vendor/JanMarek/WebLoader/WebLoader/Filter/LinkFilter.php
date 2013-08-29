<?php

namespace WebLoader\Filter;

use \Nette\Utils\Strings,
	\Nette\Latte\Parser;

/**
 * Filter for replacing links for WebLoader
 *
 * @author Martin Jantošovič <martin.jantosovic@freya.sk>
 */
class LinkFilter {

	/** @var string */
	private $start = '{{link';

	/** @var string */
	private $end = '}}';

	/** @var Nette\Application\IPresenter */
	private $presenter;

	/**
	 * Construct
	 * @param Nette\Application\IPresenter $presenter
	 */
	public function __construct(\Nette\Application\IPresenter $presenter) {
		$this->presenter = $presenter;
	}

	/**
	 * Set delimiter
	 * @param string $start
	 * @param string $end
	 * @return LinkFilter
	 */
	public function setDelimiter($start, $end) {
		$this->start = (string)$start;
		$this->end = (string)$end;
		return $this;
	}

	/**
	 * Invoke filter
	 * @param string $code
	 * @return string
	 */
	public function __invoke($code) {
		$start = $this->start;
		$end = $this->end;
		$presenter = $this->presenter;

		$code = Strings::replace($code, '/' . preg_quote($start) . ' *([^ ]+?)( .*?)? *' . preg_quote($end) . '/',
			function($match) use ($presenter) {
				$args = [];
				if (isset($match[2]) && $match[2]) {
					$argsMatch = Strings::matchAll($match[2], '/ *((' . Parser::RE_STRING . '|\w+) *=> *)?(' . Parser::RE_STRING . '|\w+) *,? */');
					foreach ($argsMatch as $m) {
						$value = trim(trim($m[3], "'"), '"');
						$key = $m[2] ? trim(trim($m[2], "'"), '"') : NULL;
						if ($key)
							$args[$key] = $value;
						else
							$args[] = $value;
					}
				}
				$destination = $match[1];
				return (string) $presenter->link($destination, $args);
			}
		);

		return $code;
	}

	/**
	 * Presenter is not serializable
	 */
	public function __sleep() {
		return [ 'start', 'end' ];
	}

}
