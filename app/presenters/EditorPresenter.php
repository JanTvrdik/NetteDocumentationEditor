<?php
namespace App;

use Github;
use Nette;
use Nette\Application\UI;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Strings;


final class EditorPresenter extends UI\Presenter
{

	/**
	 * @var EditorModel
	 * @inject
	 */
	public $editorModel;

	public function renderDefault($branch, $path)
	{
		$editor = $this['editor'];
		if ($branch && $path) {
			$form = $editor['form'];
			$page = $this->editorModel->loadPage($branch, $path);

			if ($page) {
				$editor->originalContent = $page->content;
				$form->setDefaults([
					'page' => "{$page->branch}:{$page->path}",
					'branch' => $page->branch,
					'path' => $page->path,
					'prevBlobHash' => $page->prevBlobHash,
					'texyContent' => $page->content,
				]);

			} else {
				$form->setDefaults([
					'page' => "{$branch}:{$path}",
					'branch' => $branch,
					'path' => $path,
				]);
			}

		} else {
			$editor->enableSave = FALSE;
		}

		$this->template->page = isset($page) ? $page : NULL;
	}

	public function renderView($branch, $path)
	{
		$page = $this->editorModel->loadPage($branch, $path);
		if (!$page) $this->error();

		$content = $this->context->pageRenderer->render($page);
		$this->sendResponse(new Nette\Application\Responses\TextResponse($content));
	}

	/**
	 * @return LiveTexyEditorControl
	 */
	protected function createComponentEditor()
	{
		$control = new LiveTexyEditorControl($this->context->pageRenderer);
		$control['form-open']->onClick[] = $this->processEditorOpen;
		$control['form-save']->onClick[] = $this->processEditorSave;

		return $control;
	}

	public function processEditorOpen(SubmitButton $button)
	{
		// TODO: persist potentially unsaved page

		$form = $button->form;
		$page = $form['page']->value;

		// branch:path
		if ($m = Strings::match($page, '#^([a-z0-9.-]+):([a-z][a-z0-9._/-]+)\z#')) {
			list(, $branch, $path) = $m;

		// page URL
		} else {
			try {
				list($branch, $path) = $this->editorModel->urlToRepoPath($page);
			} catch (InvalidArgumentException $e) {
				$form->addError('Invalid page identifier.');
				return;
			}
		}

		$this->redirect('this', [
			'branch' => $branch,
			'path' => $path,
		]);
	}

	public function processEditorSave(SubmitButton $button)
	{
		$form = $button->form;
		$values = $form->values;

		$page = new Page();
		$page->branch = $values->branch;
		$page->path = $values->path;
		$page->prevBlobHash = $values->prevBlobHash;
		$page->message = $values->message;
		$page->content = $values->texyContent;

		$pageKey = Strings::random(10);
		$this->getSession(__CLASS__)->pages[$pageKey] = $page;

		$url = new Nette\Http\Url('https://github.com/login/oauth/authorize');
		$url->setQuery([
			'client_id' => $this->context->parameters['github']['clientId'],
			'scope' => 'user:email',
			'redirect_uri' => $this->link('//authorized', ['pageKey' => $pageKey]),
		]);
		$this->redirectUrl($url);
	}

	public function actionAuthorized($pageKey, $code)
	{
		if (!$pageKey || !$code) $this->error();

		$session = $this->getSession(__CLASS__);
		if (!isset($session->pages[$pageKey])) {
			$this['editor']->flashMessage('Invalid page key.', 'error');
			$this->redirect('default');
		}

		$page = $session->pages[$pageKey];
		$accessToken = $this->editorModel->getAccessToken($code);
		if ($accessToken === FALSE) {
			$this['editor']->flashMessage('Failed to acquire user access token.', 'error');
			$this->redirect('default', ['branch' => $page->branch, 'path' => $page->path]);
		}

		try {
			$response = $this->editorModel->savePage($page, $accessToken);

		} catch (PermissionDeniedException $e) {
			$ghParams = $this->context->parameters['github'];
			$repo = $ghParams['repoOwner'] . '/' . $ghParams['repoName'];
			$this['editor']->flashMessage("You don't have permissions to commit to $repo and pull request support is not implemented.", 'error');
			$this->redirect('default', ['branch' => $page->branch, 'path' => $page->path]);

		} catch (PageSaveConflictException $e) {
			$this['editor']->flashMessage('Unable to save page, because someone has changed it before you. Please reopen the page to get up to date content.', 'error');
			$this->redirect('default', ['branch' => $page->branch, 'path' => $page->path]);
		}

		// build flash message
		$commitUrl = str_replace('/commits/', '/commit/', $response->getContent()['commit']['html_url']); // fix gh bug
		$msg = Nette\Utils\Html::el();
		$msg->add('Page successfully saved. ');
		$msg->create('a', 'View commit')
			->setHref($commitUrl)
			->setTarget('_blank');
		$msg->add('.');

		$this['editor']->flashMessage($msg);
		$this->redirect('default', ['branch' => $page->branch, 'path' => $page->path]);
	}

}
