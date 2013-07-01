<?php
namespace App;


final class EditorPresenter extends BasePresenter
{

	/**
	 * @return LiveTexyEditorControl
	 */
	protected function createComponentEditor()
	{
		return new LiveTexyEditorControl($this->context->texyFactory);
	}

}
