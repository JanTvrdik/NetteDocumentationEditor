<?php
namespace App;

use Github;
use Nette;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Strings;


final class EditorPresenter extends BasePresenter
{

	/**
	 * @var IEditorModel
	 * @inject
	 */
	public $editorModel;

	/**
	 * @var PageRenderer
	 * @inject
	 */
	public $pageRenderer;

	/**
	 * @var WebRepoMapper
	 * @inject
	 */
	public $webRepoMapper;

	/**
	 * @var string
	 * @persistent
	 */
	public $branch;

	/**
	 * @var string
	 * @persistent
	 */
	public $path;


	public function renderDefault($branch, $path, $pageKey)
	{
		if ($branch && $path) {
			$this['form']->setDefaults([
				'page' => $branch . ':' . $path,
				'branch' => $branch,
				'path' => $path,
			]);

			$enableSave = TRUE;
			if ($pageKey) {
				$session = $this->getSession(__CLASS__);
				if (!isset($session->pages[$pageKey])) {
					$this->flashMessage('Invalid page key.', 'error');
					$this->redirect('default');
				}
				$page = $session->pages[$pageKey];
				if ($page->branch !== $branch || $page->path !== $path) {
					$this->flashMessage('Invalid page key.', 'error');
					$this->redirect('default');
				}

			} else {
				$page = $this->editorModel->loadPage($branch, $path);
			}

			if ($page) {
				$this['form']->setDefaults([
					'prevBlobHash' => $page->prevBlobHash,
					'texyContent' => $page->content,
				]);
			}

			if ($this->signal === NULL) {
				$post = $this->request->post;
				if (!empty($post['texyContent']) && !empty($post['authorName']) && !empty($post['authorEmail'])) {
					$this['form']->setDefaults($post);
				}
			}

		} else {
			$enableSave = FALSE;
			$page = NULL;
		}

		$this->template->page = $page;
		$this->template->enableSave = $enableSave;
		$this->template->ghLink = $page ? "https://github.com/nette/web-content/blob/{$page->branch}/{$page->path}" : NULL;
		$this->template->form = $this['form'];
		$this->template->branches = $this->editorModel->getBranches();
	}

	public function renderView($branch, $path)
	{
		$page = $this->editorModel->loadPage($branch, $path);
		if (!$page) $this->error();

		$content = $this->pageRenderer->render($page);
		$this->sendResponse(new TextResponse($content));
	}


// === Opening new page ================================================================================================

	public function processEditorOpen(SubmitButton $button)
	{
		// TODO: persist potentially unsaved page

		$form = $button->form;
		if ($id = $this->webRepoMapper->toRepo($form['page']->value)) {
			list($branch, $path) = $id;
			$fragment = $form['panels']->value;
			$this->redirect('this#' . $fragment, ['branch' => $branch, 'path' => $path]);

		} else {
			$form->addError('Invalid page identifier.');
		}
	}


// === Saving page =====================================================================================================

	public function processEditorSave(SubmitButton $button)
	{
		$form = $button->form;
		$values = $form->values;

		$page = new RepoPage();
		$page->branch = $values->branch;
		$page->path = $values->path;
		$page->prevBlobHash = $values->prevBlobHash;
		$page->message = $values->message;
		$page->content = $values->texyContent;
		$page->authorName = $values->authorName;
		$page->authorEmail = $values->authorEmail;

		if ($this->editorModel instanceof EditorLocalModel) {
			$this->editorModel->savePage($page);
			$this->flashMessage('Page successfully saved.');
			$this->redirect('default');

		} else {
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
			$this->redirect('default', ['pageKey' => $pageKey]);
		}

		try {
			$response = $this->editorModel->savePage($page, $accessToken);
			unset($session->pages[$pageKey]);

		} catch (PermissionDeniedException $e) {
			$ghParams = $this->context->parameters['github'];
			$repo = $ghParams['repoOwner'] . '/' . $ghParams['repoName'];
			$this->flashMessage("You don't have permissions to commit to $repo and pull request support is not implemented.", 'error');
			$this->redirect('default', ['pageKey' => $pageKey]);

		} catch (PageSaveConflictException $e) {
			$this->flashMessage('Unable to save page, because someone has changed it before you. Please reopen the page to get up to date content.', 'error');
			$this->redirect('default', ['pageKey' => $pageKey]);
		}

		// build flash message
		$commitUrl = str_replace('/commits/', '/commit/', $response->getContent()['commit']['html_url']); // fix gh bug
		$msg = Nette\Utils\Html::el();
		$msg->add('Page successfully saved. ');
		$msg->create('a', 'View commit')
			->setHref($commitUrl)
			->setTarget('_blank');
		$msg->add('.');

		$this->flashMessage($msg);
		$this->redirect('default');
	}


// === Preview ========================================================================================================

	public function handleRenderPreview($branch, $path, $texyContent)
	{
		$page = new RepoPage();
		$page->branch = $branch;
		$page->path = $path;
		$page->content = $texyContent;

		$htmlContent = $this->pageRenderer->render($page, FALSE, TRUE);
		$this->editorModel->savePageDraft($page);

		$this->payload->htmlContent = $htmlContent;
		$this->sendPayload();
	}


// === Search ==========================================================================================================

	public function handleSearch($tree)
	{
		$this->payload->pages = $this->editorModel->getPages($tree);
		$this->sendPayload();
	}


// === Component factories =============================================================================================

	protected function createComponentForm()
	{
		$this->session->start();

		$form = new UI\Form();
		$form->addProtection();

		$form->addText('page')
			->setRequired('Please specify which page to open.');
		$form->addSubmit('open')
			->setValidationScope([$form['page'], $form[UI\Form::PROTECTOR_ID]])
			->onClick[] = $this->processEditorOpen;

		$form->addText('message')
			->setRequired('Please fill commit message.');
		$form->addTextArea('texyContent');
		$form->addHidden('authorName');
		$form->addHidden('authorEmail');
		$form->addHidden('branch');
		$form->addHidden('path');
		$form->addHidden('prevBlobHash');
		$form->addSubmit('save')
			->setValidationScope([$form['message'], $form['texyContent'], $form[UI\Form::PROTECTOR_ID]])
			->onClick[] = $this->processEditorSave;

		$form->addSelect('panels', NULL, [
			'code' => 'code only',
			'code+preview' => 'code and preview',
			'code+diff' => 'code and diff',
			'preview' => 'preview only',
			'diff' => 'diff only',
		])->setDefaultValue('code+preview');

		return $form;
	}

}
