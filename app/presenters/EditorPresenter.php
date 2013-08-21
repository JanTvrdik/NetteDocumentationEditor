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

	/**
	 * @var Github\Client
	 * @inject
	 */
	public $ghClient;

	public function renderDefault($branch, $path)
	{
		if ($branch && $path) {
			try {
				$file = $this->editorModel->loadPage($branch, $path);
				if ($file['encoding'] !== 'base64') throw new NotSupportedException();
				$form = $this['editor-form']->setDefaults([
					'page' => "$branch:$path",
					'branch' => $branch,
					'path' => $path,
					'prevBlogHash' => $file['sha'],
					'texyContent' => base64_decode($file['content']),
				]);

			} catch (\Github\Exception\RuntimeException $e) {
				if ($e->getCode() === 404) {
					$this['editor-form']->addError('Page not found.');
				} else {
					throw $e;
				}
			}
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
		if ($m = Strings::match($page, '#^([a-z0-9.-]+):([a-z0-9._/-]+)\z#')) {
			list(, $branch, $path) = $m;
		} else {
			if (!Strings::startsWith($page, 'http://')) $page = "http://$page";
			try {
				list($branch, $path) = $this->editorModel->urlToRepoPath($page);
			} catch (InvalidArgumentException $e) {
				$form->addError('Invalid page identifier.');
				return;
			}
		}

		if (!$this->editorModel->pageExists($branch, $path)) {
			$form->addError('Page not found.');
			return;
		}

		$this->redirect('this', array(
			'branch' => $branch,
			'path' => $path,
		));
	}

	public function processEditorSave(UI\Form $form)
	{
		$values = $form->values;
		$page = new Page();
		$page->branch = $values->branch;
		$page->path = $values->path;
		$page->prevBlobHash = $values->prevBlobHash;
		$page->content = $values->texyContent;

		$session = $this->getSession(__CLASS__);
		if ($session->accessToken === NULL) {
			$pageKey = Strings::random(10);
			$session->pages[$pageKey] = $page;

			$url = new Nette\Http\Url('https://github.com/login/oauth/authorize');
			$url->setQuery([
				'client_id' => $this->context->parameters['github']['clientId'],
				'redirect_uri' => $this->link('//authorized', array('pageKey' => $pageKey)),
				'state' => Strings::random(20),
			]);
			$this->redirectUrl($url);

		} else {
			$this->createCommit($page, $session->accessToken);
		}
	}

	public function actionAuthorized($pageKey, $code)
	{
		$session = $this->getSession(__CLASS__);
		if (!isset($session->pages[$pageKey])) {
			$this->flashMessage('Invalid page key.', 'error');
			$this->redirect('default');
		}

		$page = $session->pages[$pageKey];
		$accessToken = $this->getAccessToken($code);
		$this->createCommit($page, $accessToken);
	}

	private function getAccessToken($code)
	{
		$ghParams = $this->context->parameters['github'];
//		$response = $this->ghClient->getHttpClient()->post('login/oauth/access_token', [
//			'client_id' => $ghParams['clientId'],
//			'client_secret' => $ghParams['clientSecret'],
//			'code' => $code,
//		]);
//
//		return $response->getContent()['access_token'];

		$ghParams = $this->context->parameters['github'];
		$context = stream_context_create([
				'http' => [
					'method' => 'POST',
					'header' => ['Content-Type: application/x-www-form-urlencoded'],
					'content' => http_build_query([
							'client_id' => $ghParams['clientId'],
							'client_secret' => $ghParams['clientSecret'],
							'code' => $code,
						]),
				]
			]);

		$response = file_get_contents('https://github.com/login/oauth/access_token', NULL, $context);
		parse_str($response, $params);

		dump($params);

		return $params['access_token'];
	}

	private function createCommit(Page $page, $accessToken)
	{
		$this->ghClient->authenticate($accessToken, Github\Client::AUTH_HTTP_TOKEN);
		$this->ghClient->getHttpClient()->put(
			sprintf(
				'/repos/%s/%s/contents/%s',
				urlencode('JanTvrdik'), urlencode('web-content'), urlencode($page->path) // TODO: use values from config
			), [
				'message' => $page->message,
				'content' => $page->content,
				'sha' => $page->prevBlobHash,
				'branch' => $page->branch,
				// 'committer.name' =>
				// 'committer.email'
			]
		);
	}

	/**
	 * @return UI\Form
	 */
	protected function createComponentOpenPageForm()
	{
		$form = new UI\Form();
		$form->addText('page')
			->addRule($form::URL)
			->setRequired();
		$form->addSubmit('send', 'Send');
		$form->onSuccess[] = $this->openPageFormSubmitted;

		return $form;
	}


	/**
	 * @param UI\Form $form
	 */
	public function openPageFormSubmitted(UI\Form $form)
	{
		$pageUrl = $form->values->page;
		list($branch, $path) = $this->editorModel->urlToRepoPath($pageUrl);
		$this->redirect('this', array(
			'branch' => $branch,
			'path' => $path,
		));
	}

}
