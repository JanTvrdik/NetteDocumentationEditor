<?php
namespace App;

use Nette;
use Nette\Application\UI;


class PageRendererControl extends UI\Control
{

	/** @var Page */
	public $page;

	public function render()
	{
		$book = $lang = $name = 'x';

		$convertor = new \Text\Convertor($book, $lang, $name);
		$convertor->parse($this->page->content);

		$this->template->setFile(__DIR__ . '/PageRenderer.latte');
		$this->template->htmlContent = $convertor->html;
		$this->template->toc = $convertor->toc;
		$this->template->render();
	}

}
