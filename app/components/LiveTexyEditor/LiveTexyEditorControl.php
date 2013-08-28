<?php
namespace App;

use Nette;
use Nette\Application\UI;


class LiveTexyEditorControl extends UI\Control
{

	/** @var bool */
	public $enableSave = TRUE;

	/** @var string */
	public $originalContent = '';

	/** @var PageRenderer */
	private $pageRenderer;

	public function __construct(PageRenderer $pageRenderer)
	{
		$this->pageRenderer = $pageRenderer;
	}

	public function render()
	{
		$this->template->setFile(__DIR__ . '/LiveTexyEditorControl.latte');
		$this->template->enableSave = $this->enableSave;
		$this->template->originalContent = $this->originalContent;
		$this->template->render();
	}

	public function handleRenderPreview($texyContent)
	{
		$page = new Page();
		$page->branch = $this->presenter->getParameter('branch');
		$page->path = $this->presenter->getParameter('path');
		$page->content = $texyContent;

		$htmlContent = $this->pageRenderer->render($page, TRUE);

		$this->presenter->payload->htmlContent = $htmlContent;
		$this->presenter->sendPayload();
	}

	/**
	 * @return UI\Form
	 */
	protected function createComponentForm()
	{
		$form = new UI\Form();

		$form->addText('page')
			->setRequired('Please specify which page to open.');
		$form->addSubmit('open')
			->setValidationScope([$form['page']]);

		$form->addText('message')
			->setRequired('Please fill commit message.');
		$form->addTextArea('texyContent');
		$form->addHidden('branch');
		$form->addHidden('path');
		$form->addHidden('prevBlobHash');
		$form->addSubmit('save')
			->setValidationScope([$form['message'], $form['texyContent']]);

		$form->addSelect('panels', NULL, [
			'code' => 'code only',
			'code preview' => 'code and preview',
			'code diff' => 'code and diff',
			'preview' => 'preview only',
			'diff' => 'diff only',
		])->setDefaultValue('code preview');

		return $form;
	}

}
