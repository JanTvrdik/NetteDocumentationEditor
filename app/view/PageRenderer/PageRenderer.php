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

	/** @var WebRepoMapper */
	private $webRepoMapper;

	public function __construct(Nette\Templating\FileTemplate $template, LinkFactory $linkFactory, WebRepoMapper $webRepoMapper)
	{
		$this->template = $template;
		$this->linkFactory = $linkFactory;
		$this->webRepoMapper = $webRepoMapper;
	}

	public function render(Page $page, Page $menu = NULL, $forceNewWindow = FALSE)
	{
		if ($page->branch === NULL && $page->path === NULL) {
			$web = ['xxx', 'en', 'homepage'];

		} else {
			$web = $this->webRepoMapper->repoToWeb($page->branch, $page->path);
		}

		if ($web) {
			list($book, $lang, $name) = $web;
			$converter = new TextConverter($book, $lang, $name);
			$converter->paths['apiUrl'] = 'http://api.nette.org/' . $this->getApiVersion($page->branch);
			$converter->paths['profileUrl'] = 'http://forum.nette.org/cs/profile.php?id=';
			$converter->imageRoot = "https://raw.github.com/nette/web-content/{$page->branch}/files";
			$converter->linkFactory = function (\Text\Link $link) {
				$fragment = ($link->fragment ? ('#' . $link->fragment) : '');
				list($branch, $path) = $this->webRepoMapper->webToRepo($link->book, $link->lang, Strings::webalize($link->name, '/'));
				return $this->linkFactory->link('Editor:view' . $fragment, [
					'branch' => $branch,
					'path' => $path,
				]);
			};

			$converter->parse($page->content);

			if ($forceNewWindow) {
				$converter->html = Strings::replace($converter->html, '~<a(\s+)(?!href="#)~', '<a target="_blank"$1');
			}

			$this->template->title = $converter->title;
			$this->template->themeIcon = $converter->themeIcon;
			$this->template->toc = $converter->toc;
			$this->template->theme = $converter->theme;
			$this->template->htmlContent = $converter->html;
			$this->template->netteOrgLink = $this->webRepoMapper->webToUrl($book, $lang, $name);

			if ($menu) {
				$converter->parse($menu->content);
				$this->template->topMenu = $converter->html;
				$this->template->homepageLink = $this->linkFactory->link('Editor:view', ['branch' => 'www', 'path' => $lang . '/' . 'homepage.texy']);
			}

		} else {
			// assume plain-text
			$this->template->title = NULL;
			$this->template->themeIcon = NULL;
			$this->template->toc = NULL;
			$this->template->theme = NULL;
			$this->template->htmlContent = nl2br(htmlspecialchars($page->content), FALSE);
		}

		$this->template->ghLink = "https://github.com/nette/web-content/blob/{$page->branch}/{$page->path}";
		$this->template->editLink = $this->linkFactory->link('Editor:default', ['branch' => $page->branch, 'path' => $page->path]);
		$this->template->setFile(__DIR__ . '/PageRenderer.latte');
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

}
