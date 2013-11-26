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
	 * @var EditorModel
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


	public function renderDefault($branch, $path)
	{
		if ($branch && $path) {
			$this['form']->setDefaults([
				'page' => $branch . ':' . $path,
				'branch' => $branch,
				'path' => $path,
			]);

			$enableSave = TRUE;
			$page = $this->editorModel->loadPage($branch, $path);
			if ($page) {
				$this['form']->setDefaults([
					'prevBlobHash' => $page->prevBlobHash,
					'texyContent' => $page->content,
				]);
			}

		} else {
			$enableSave = FALSE;
			$page = NULL;
		}

		$this->template->page = $page;
		$this->template->enableSave = $enableSave;
		$this->template->ghLink = $page ? "https://github.com/nette/web-content/blob/{$page->branch}/{$page->path}" : NULL;
		$this->template->form = $this['form'];
	}

	public function renderView($branch, $path)
	{
		$page = $this->editorModel->loadPage($branch, $path);
		if (!$page) $this->error();

		$web = $this->webRepoMapper->repoToWeb($page->branch, $page->path);
		if ($web) {
			$lang = $web[1];
			$menu = $this->editorModel->loadPage('meta', $lang . '/menu.texy');
		}

		$content = $this->pageRenderer->render($page, isset($menu) ? $menu : NULL);
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
			$this->redirect('default');
		}

		try {
			$response = $this->editorModel->savePage($page, $accessToken);

		} catch (PermissionDeniedException $e) {
			$ghParams = $this->context->parameters['github'];
			$repo = $ghParams['repoOwner'] . '/' . $ghParams['repoName'];
			$this->flashMessage("You don't have permissions to commit to $repo and pull request support is not implemented.", 'error');
			$this->redirect('default');

		} catch (PageSaveConflictException $e) {
			$this->flashMessage('Unable to save page, because someone has changed it before you. Please reopen the page to get up to date content.', 'error');
			$this->redirect('default');
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
		$page = new Page();
		$page->branch = $branch;
		$page->path = $path;
		$page->content = $texyContent;

		$htmlContent = $this->pageRenderer->render($page, NULL, TRUE);

		$this->payload->htmlContent = $htmlContent;
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
