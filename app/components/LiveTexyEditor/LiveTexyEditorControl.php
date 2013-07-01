<?php
namespace App;

use Nette;
use Nette\Application\UI;


class LiveTexyEditorControl extends UI\Control
{

	/** @var ITexyFactory */
	private $texyFactory;

	public function __construct(ITexyFactory $texyFactory)
	{
		$this->texyFactory = $texyFactory;
	}

	public function render()
	{
		$this->template->setFile(__DIR__ . '/LiveTexyEditorControl.latte');
		$this->template->render();
	}

	public function handleProcess($texyContent)
	{
		$texy = $this->texyFactory->create();
		$this->presenter->payload->htmlContent = $texy->process($texyContent);
		$this->presenter->sendPayload();
	}

}
