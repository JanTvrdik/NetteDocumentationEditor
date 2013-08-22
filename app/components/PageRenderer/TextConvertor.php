<?php
namespace App;

use Nette;
use Text\Link;

class TextConvertor extends \Text\Convertor
{

	/** @var callback (Text\Link) */
	public $linkFactory;

	public function createUrl(Link $link)
	{
		if ($this->linkFactory === NULL) throw new InvalidStateException();
		$callback = $this->linkFactory;
		return $callback($link);
	}

}
