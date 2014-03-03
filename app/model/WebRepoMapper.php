<?php
namespace App;

use Nette;
use Nette\Utils\Strings;


class WebRepoMapper extends Nette\Object
{

	/** string */
	const DEFAULT_BRANCH = 'nette.org';

	/** string */
	const DEFAULT_DOC_VERSION = '2.1';

	/** string */
	const DEFAULT_PAGE_NAME = 'homepage';

	/** string */
	const DEFAULT_LANG = 'en';

	/**
	 * Converts page identification in repository to its web identification.
	 *
	 * @param  string  e.g. 'pla'
	 * @param  string  e.g. 'en/homepage.texy'
	 * @return array|FALSE
	 */
	public function repoToWeb($branch, $path)
	{
		if (strpos($branch, '-')) {
			$m = Strings::match($path, '#^()([a-z]{2})/([\w/.-]+)\.texy$#');
		} else {
			$m = Strings::match($path, '#^([a-z]{3,})/([a-z]{2})/([\w/.-]+)\.texy$#');
		}
		if (!$m) return FALSE;
		return [$m[1] ?: $branch, $m[2], $m[3]];
	}

	/**
	 * Converts page web identification to its repository identification.
	 *
	 * @aaram  string e.g. 'pla'
	 * @param  string e.g. 'en'
	 * @param  string e.g. 'homepage'
	 * @return array
	 */
	public function webToRepo($book, $lang, $name)
	{
		$name = $name ?: self::DEFAULT_PAGE_NAME;
		if (Strings::startsWith($book, 'doc')) {
			if ($book === 'doc') {
				$book .= '-' . self::DEFAULT_DOC_VERSION;
			}
			return [$book, $lang . '/' . $name . '.texy'];
		} else {
			return [self::DEFAULT_BRANCH, $book . '/' . $lang . '/' . $name . '.texy'];
		}
	}

	/**
	 * Converts URL on nette.org to web identification of corresponding page.
	 *
	 * @param  string
	 * @return array|FALSE
	 */
	public function urlToWeb($url)
	{
		$m = Strings::match($url, '~^
			(?:http://)?
			(?: (?<book> [\w-]+ ) \. )?
			nette\.org
			(?: / (?<lang> [a-z]{2} ) )?
			(?: / (?<version> \d\.\d ) )?
			(?: / (?<name> [\w/.-]*  ) )?
			(?: [#?].* )?
		$~x');
		if (!$m) return FALSE;
		$book = (!empty($m['book']) ? $m['book'] : 'www');
		if ($book === 'doc') {
			$book .= '-' . (!empty($m['version']) ? $m['version'] : self::DEFAULT_DOC_VERSION);
		}
		$lang = !empty($m['lang']) ? $m['lang'] : 'en';
		$name = !empty($m['name']) ? rtrim($m['name'], '/') : self::DEFAULT_PAGE_NAME;
		return [$book, $lang, $name];
	}

	public function webToUrl($book, $lang, $name)
	{
		$parts = explode('-', $book);
		$sub = ($parts[0] === 'www' ? '' : $parts[0] . '.');
		$version = isset($parts[1]) ? '/' . $parts[1] : '';
		$name = ($name === self::DEFAULT_PAGE_NAME ? '' : $name);
		return 'http://' . $sub . 'nette.org/' . $lang . $version . '/' . $name;
	}

	/**
	 * Converts URL on nette.org to repository identification of corresponding page.
	 *
	 * @param  string
	 * @return array|FALSE
	 */
	public function urlToRepo($url)
	{
		$web = $this->urlToWeb($url);
		if (!$web) return FALSE;
		return $this->webToRepo($web[0], $web[1], $web[2]);
	}

	public function toRepo($str)
	{
		$patterns = [
			// doc-2.1:en/arrays.texy
			'#^
				(?<branch>[\w.-]+)
				:
				(?<path>
					[\w.-]+
					(?: / [\w.-]+ )*
				)
			$#x',
			// https://github.com/nette/web-content/blob/doc-2.1/en/arrays.texy
			'~^
				(?:https?://)?
				github.com/nette/web-content/blob/
				(?<branch>[\w.-]+)
				/
				(?<path>
					[\w.-]+
					(?: / [\w.-]+ )*
				)
				(?: [#?].* )?
			$~x',
			// https://raw.github.com/nette/web-content/doc-2.1/readme.md
			'~^
				(?:https?://)?
				raw.github.com/nette/web-content/
				(?<branch>[\w.-]+)
				/
				(?<path>
					[\w.-]+
					(?: / [\w.-]+ )*
				)
				(?: [#?].* )?
			$~x',
		];

		foreach ($patterns as $pattern) {
			$match = Strings::match($str, $pattern);
			if ($match) {
				$branch = $match['branch'];
				$path = $match['path'];
				return [$branch, $path];
			}
		}

		return $this->urlToRepo($str);
	}

}
