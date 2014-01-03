<?php
namespace App;

use Nette;
use Text\Link;

class TextConverter extends \Text\Converter
{

	/** @var callback (Text\Link) */
	public $linkFactory;

	/** @var string */
	public $imageRoot;

	public function createUrl(Link $link)
	{
		if ($this->linkFactory === NULL) throw new InvalidStateException();
		$callback = $this->linkFactory;
		return $callback($link);
	}

	public function createTexy()
	{
		if ($this->imageRoot === NULL) throw new InvalidStateException();
		$texy = parent::createTexy();
		$texy->imageModule->root = $this->imageRoot;
		return $texy;
	}

}
