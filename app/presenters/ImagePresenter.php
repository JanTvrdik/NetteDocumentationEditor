<?php
namespace App;

use Nette;
use Nette\Application;
use Nette\Http;


final class ImagePresenter extends Nette\Object implements Application\IPresenter
{

	/**
	 * @var IEditorModel
	 * @inject
	 */
	public $editorModel;

	/**
	 * @var Http\IResponse
	 * @inject
	 */
	public $httpResponse;

	/**
	 * @return IResponse
	 */
	public function run(Application\Request $request)
	{
		if (!$request->isMethod('GET')) {
			return NULL;
		}

		$path = $request->getParameter('path');
		if (!is_string($path)) {
			return NULL;
		}

		$binary = $this->editorModel->loadImage($path);
		if (!$binary) {
			throw new Application\BadRequestException('', 404);
		}

		$mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $binary);
		$this->httpResponse->setContentType($mime);
		$this->httpResponse->setExpiration(15 * 60);
		return new Nette\Application\Responses\TextResponse($binary);
	}

}
