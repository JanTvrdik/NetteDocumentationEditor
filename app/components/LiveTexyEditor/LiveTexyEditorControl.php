<?php
namespace App;

use Nette;
use Nette\Application\UI;


class LiveTexyEditorControl extends UI\Control
{
	public function render()
	{
		$this->template->setFile(__DIR__ . '/LiveTexyEditorControl.latte');
		$this->template->render();
	}

	public function handleRenderPreview($texyContent, $book, $lang, $name)
	{
		$convertor = new \Text\Convertor($book, $lang, $name);
		$convertor->parse($texyContent);

		$tpl = $this->createTemplate();
		$tpl->setFile(__DIR__ . '/preview.latte');
		$tpl->htmlContent = $convertor->html;
		$tpl->toc = $convertor->toc;

		$this->presenter->payload->htmlContent = (string) $tpl;
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

}
