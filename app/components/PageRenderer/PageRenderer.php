<?php
namespace App;

use Nette;
use Nette\Utils\Strings;


class PageRenderer extends Nette\Object
{

	/** @var Nette\Templating\FileTemplate */
	private $template;

	/** @var LinkFactory */
	private $linkFactory;

	public function __construct(Nette\Templating\FileTemplate $template, LinkFactory $linkFactory)
	{
		$this->template = $template;
		$this->linkFactory = $linkFactory;
	}

	public function render(Page $page, $forceNewWindow = FALSE)
	{
		$convertor = new TextConvertor($page->book, $page->lang, $page->name);
		$convertor->paths['apiUrl'] = 'http://api.nette.org/' . $this->getApiVersion($page->branch);
		$convertor->paths['profileUrl'] = 'http://forum.nette.org/cs/profile.php?id=';
		$convertor->imageRoot = "https://raw.github.com/nette/web-content/{$page->branch}/files";
		$convertor->linkFactory = function (\Text\Link $link) {
			$fragment = ($link->fragment ? ('#' . $link->fragment) : '');
			return $this->linkFactory->link('Editor:view' . $fragment, [
				'branch' => $this->getBranch($link->book),
				'path' => $link->lang . '/' . Strings::webalize($link->name) . '.texy',
			]);
		};

		$convertor->parse($page->content);

		if ($forceNewWindow) {
			$convertor->html = Strings::replace($convertor->html, '~<a(\s+)(?!href="#)~', '<a target="_blank"$1');
		}

		$this->template->setFile(__DIR__ . '/PageRenderer.latte');
		$this->template->title = $convertor->title;
		$this->template->themeIcon = $convertor->themeIcon;
		$this->template->htmlContent = $convertor->html;
		$this->template->toc = $convertor->toc;
		return (string) $this->template;
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
