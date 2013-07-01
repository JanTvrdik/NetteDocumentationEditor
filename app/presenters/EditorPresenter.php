<?php
namespace App;

use Nette\Application\UI;


final class EditorPresenter extends BasePresenter
{

	/**
	 * @var EditorModel
	 * @inject
	 */
	public $editorModel;

	public function renderDefault($branch, $path)
	{
		if ($branch && $path) {
			try {
				$this['editor']->content = $this->editorModel->loadPageContent($branch, $path);
			} catch (\Github\Exception\RuntimeException $e) {
				if ($e->getCode() === 404) $this->error();
				throw $e;
			}
		}
	}

	/**
	 * @return LiveTexyEditorControl
	 */
	protected function createComponentEditor()
	{
		return new LiveTexyEditorControl($this->context->texyFactory);
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
