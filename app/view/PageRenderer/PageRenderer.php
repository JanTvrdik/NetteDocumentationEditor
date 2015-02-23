<?php
namespace App;

use Nette;
use Nette\Utils\Strings;


class PageRenderer extends Nette\Object
{

	/** @var Nette\Application\UI\ITemplate */
	private $templateFactory;

	/** @var LinkFactory */
	private $linkFactory;

	/** @var WebRepoMapper */
	private $webRepoMapper;

	/** @var IEditorModel */
	private $model;

	public function __construct(Nette\Application\UI\ITemplateFactory $templateFactory, LinkFactory $linkFactory, WebRepoMapper $webRepoMapper, IEditorModel $model)
	{
		$this->templateFactory = $templateFactory;
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

		$template = $this->templateFactory->createTemplate();

		if ($web) {
			list($book, $lang, $name) = $web;
			$converter = new TextConverter($book, $lang, $name);
			$converter->paths['apiUrl'] = 'http://api.nette.org/' . $this->getApiVersion($page->branch);
			$converter->paths['profileUrl'] = 'http://forum.nette.org/cs/profile.php?id=';
			$converter->imageRoot = $this->linkFactory->link('Image:view', [
				'branch' => $page->branch,
				'path' => ($page->branch === WebRepoMapper::DEFAULT_BRANCH ? "$book/" : '') . 'files/',
			]);
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

			$template->title = $converter->title;
			$template->themeIcon = $converter->themeIcon;
			$template->toc = $converter->toc;
			$template->theme = $converter->theme;
			$template->htmlContent = $converter->html;
			$template->netteOrgLink = $this->webRepoMapper->webToUrl($book, $lang, $name);

			if ($header) {
				if ($menu = $this->model->loadPage('nette.org', "meta/$lang/menu.texy")) {
					$converter->parse($menu->content);
					$template->topMenu = $converter->html;
				}

				if (Strings::startsWith($book, 'doc-') && $name !== 'homepage') {
					if ($docMenu = $this->model->loadPage($page->branch, "$lang/@docmenu.texy")) {
						$converter->current = new \Text\Link('doc-2.1', $lang, 'homepage');
						$converter->topHeadingLevel = 3;
						$converter->parse($docMenu->content);
						$template->docMenu = $converter->html;
					}
				}

				$template->homepageLink = $this->linkFactory->link('Editor:view', ['branch' => 'nette.org', 'path' => "www/$lang/homepage.texy"]);
			}

		} else {
			// assume plain-text
			$template->title = NULL;
			$template->themeIcon = NULL;
			$template->toc = NULL;
			$template->theme = NULL;
			$template->htmlContent = nl2br(htmlspecialchars($page->content), FALSE);
		}

		$template->ghLink = "https://github.com/nette/web-content/blob/{$page->branch}/{$page->path}";
		$template->editLink = $this->linkFactory->link('Editor:default', ['branch' => $page->branch, 'path' => $page->path]);
		$template->setFile(__DIR__ . '/PageRenderer.latte');
		return (string) $template;
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
