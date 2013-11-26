<?php
namespace App;

use Nette\Application\UI;
use WebLoader;


abstract class BasePresenter extends UI\Presenter
{

	protected function createComponentCss()
	{
		return new WebLoader\Nette\CssLoader(
			$this->context->getService('webloader.cssDefaultCompiler'),
			$this->template->basePath . '/webtemp'
		);
	}

	protected function createComponentJs()
	{
		return new WebLoader\Nette\JavaScriptLoader(
			$this->context->getService('webloader.jsDefaultCompiler'),
			$this->template->basePath . '/webtemp'
		);
	}

}
