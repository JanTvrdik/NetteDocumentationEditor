<?php
namespace App;

use Nette;
use Nette\Application\UI;


class LiveTexyEditorControl extends UI\Control
{

	/** @var bool */
	public $enableSave = TRUE;

	public function render()
	{
		$this->template->setFile(__DIR__ . '/LiveTexyEditorControl.latte');
		$this->template->enableSave = $this->enableSave;
		$this->template->render();
	}

	public function handleRenderPreview($texyContent)
	{
		$page = new Page();
		$page->branch = $this->presenter->getParameter('branch');
		$page->path = $this->presenter->getParameter('path');
		$page->content = $texyContent;

		ob_start();
		$preview = $this['preview'];
		$preview->page = $page;
		$preview->forceNewWindow = TRUE;
		$preview->render();
		$htmlContent = ob_get_clean();

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
			'code' => 'code',
			'preview' => 'preview',
			'code preview' => 'code and preview',
		])->setDefaultValue('code preview');

		return $form;
	}

	/**
	 * @return PageRendererControl
	 */
	protected function createComponentPreview()
	{
		return new PageRendererControl();
	}

}
