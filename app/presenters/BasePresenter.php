<?php
namespace App;

use Nette\Application\UI;
use WebLoader;


abstract class BasePresenter extends UI\Presenter
{

	/**
	 * @var WebLoader\LoaderFactory
	 * @inject
	 */
	public $webLoader;

	protected function createComponentCss()
	{
		return $this->webLoader->createCssLoader('default');
	}

	protected function createComponentJs()
	{
		return $this->webLoader->createJavaScriptLoader('default');
	}

}
