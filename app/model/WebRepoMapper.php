<?php
namespace App;

use Nette;
use Nette\Utils\Strings;


class WebRepoMapper extends Nette\Object
{

	private $branch2book = [
		'doc-2.0' => 'doc',
		'doc-0.9' => 'doc09',
	];

	/**
	 * Converts page identification in repository to its web identification.
	 *
	 * @param  string  e.g. 'pla'
	 * @param  string  e.g. 'en/homepage.texy'
	 * @return array|FALSE
	 */
	public function repoToWeb($branch, $path)
	{
		if (substr($path, -5) !== '.texy') return FALSE;
		$path = substr($path, 0, -5);
		$m = Strings::match($path, '#^([a-z]{2})/([\w/.-]+)$#');
		if (!$m) return FALSE;
		list(, $lang, $name) = $m;
		$book = $this->branchToBook($branch);
		return [$book, $lang, $name];
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
		$branch = $this->bookToBranch($book);
		$path = $lang . '/' . $name . '.texy';
		return [$branch, $path];
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
			(?: / (?<name> [\w/.-]*  ) )?
			(?: [#?].* )?
		$~x');
		if (!$m) return FALSE;
		$book = !empty($m['book']) ? $m['book'] : 'www';
		$lang = !empty($m['lang']) ? $m['lang'] : 'en';
		$name = !empty($m['name']) ? rtrim($m['name'], '/') : 'homepage';
		return [$book, $lang, $name];
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
		if (Strings::match($str, '#^([\w.-]+):([\w/.-]+)$#')) {
			return explode(':', $str);
		} else {
			return $this->urlToRepo($str);
		}
	}

	public function bookToBranch($book)
	{
		return array_search($book, $this->branch2book) ?: $book;
	}

	public function branchToBook($branch)
	{
		return (isset($this->branch2book[$branch]) ? $this->branch2book[$branch] : $branch);
	}

}
