<?php
namespace App;

use Nette;
use Nette\Application\UI;
use Nette\Utils\Strings;


class PageRendererControl extends UI\Control
{

	/** @var Page */
	public $page;

	public function render()
	{
		$presenter = $this->presenter;
		$convertor = new TextConvertor($this->page->book, $this->page->lang, $this->page->name);
		$convertor->paths['apiUrl'] = 'http://api.nette.org/' . $this->getApiVersion($this->page->branch);
		$convertor->paths['profileUrl'] = 'http://forum.nette.org/cs/profile.php?id=';
		$convertor->linkFactory = function (\Text\Link $link) use ($presenter) {
			$fragment = ($link->fragment ? ('#' . $link->fragment) : '');
			return $presenter->link('Editor:view' . $fragment, [
				'branch' => $this->getBranch($link->book),
				'path' => $link->lang . '/' . $link->name . '.texy',
			]);
		};

		$convertor->parse($this->page->content);

		$this->template->setFile(__DIR__ . '/PageRenderer.latte');
		$this->template->htmlContent = $convertor->html;
		$this->template->toc = $convertor->toc;
		$this->template->render();
	}

	private function getApiVersion($branch)
	{
		if ($m = Strings::match($branch, '#^doc-([0-9.]+)$#')) {
			return $m[1];
		} else {
			return '2.1'; // default
		}
	}

	private function getBranch($book)
	{
		$aliases = ['doc' => 'doc-2.0', 'doc09' => 'doc-0.9'];
		return isset($aliases[$book]) ? $aliases[$book] : $book;
	}

}
