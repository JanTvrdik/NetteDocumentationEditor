<?php
namespace App;

use Nette;
use Nette\Utils\Strings;


class PageRenderer extends Nette\Object
{

	/** @var Nette\Application\UI\ITemplate */
	private $template;

	/** @var LinkFactory */
	private $linkFactory;

	/** @var WebRepoMapper */
	private $webRepoMapper;

	/** @var EditorModel */
	private $model;

	public function __construct(Nette\Application\UI\ITemplate $template, LinkFactory $linkFactory, WebRepoMapper $webRepoMapper, EditorModel $model)
	{
		$this->template = $template;
		$this->linkFactory = $linkFactory;
		$this->webRepoMapper = $webRepoMapper;
		$this->model = $model;
	}

	public function render(RepoPage $page, $header = TRUE, $forceNewWindow = FALSE)
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
			$converter->imageRoot = "https://raw.github.com/nette/web-content/{$page->branch}" . (($page->branch === 'nette.org') ? "/$book" : '') . "/files";
			$converter->linkFactory = function (\Text\Link $link) {
				$fragment = strtolower($link->fragment ? ('#' . $link->fragment) : '');
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

			if ($header) {
				if ($menu = $this->model->loadPage('nette.org', "meta/$lang/menu.texy")) {
					$converter->parse($menu->content);
					$this->template->topMenu = $converter->html;
				}

				if (Strings::startsWith($book, 'doc-') && $name !== 'homepage') {
					if ($docMenu = $this->model->loadPage($page->branch, "$lang/@docmenu.texy")) {
						$converter->current = new \Text\Link('doc-2.1', $lang, 'homepage');
						$converter->topHeadingLevel = 3;
						$converter->parse($docMenu->content);
						$this->template->docMenu = $converter->html;
					}
				}

				$this->template->homepageLink = $this->linkFactory->link('Editor:view', ['branch' => 'nette.org', 'path' => "www/$lang/homepage.texy"]);
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
