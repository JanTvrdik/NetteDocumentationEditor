<?php
namespace App;

use Github;
use Nette;
use Nette\Application\UI;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Strings;


final class EditorPresenter extends BasePresenter
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
				$form->setDefaults([
					'page' => "{$page->branch}:{$page->path}",
					'branch' => $page->branch,
					'path' => $page->path,
					'prevBlobHash' => $page->prevBlobHash,
					'texyContent' => $page->content,
				]);

			} else {
				$editor->enableSave = FALSE;
				$form->addError('Page not found.');
			}

		} else {
			$editor->enableSave = FALSE;
		}
	}

	/**
	 * @return LiveTexyEditorControl
	 */
	protected function createComponentEditor()
	{
		$control = new LiveTexyEditorControl();
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

		if (!$this->editorModel->pageExists($branch, $path)) {
			$form->addError("Page ($branch:$path) not found.");
			return;
		}

		$this->redirect('this', array(
			'branch' => $branch,
			'path' => $path,
		));
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
			$this->flashMessage('Invalid page key.', 'error');
			$this->redirect('default');
		}

		$page = $session->pages[$pageKey];
		$accessToken = $this->editorModel->getAccessToken($code);
		if ($accessToken === FALSE) {
			$this->flashMessage('Failed to acquire user access token.', 'error');
			$this->redirect('default', ['branch' => $page->branch, 'path' => $page->path]);
		}

		try {
			$response = $this->editorModel->savePage($page, $accessToken);
		} catch (PermissionDeniedException $e) {
			$ghParams = $this->context->parameters['github'];
			$repo = $ghParams['repoOwner'] . '/' . $ghParams['repoName'];
			$this->flashMessage("You don't have permissions to commit to $repo and pull request support is not implemented.", 'error');
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
