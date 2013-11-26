<?php
namespace App;

use Github;
use Nette;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Strings;
use WebLoader;


final class ReviewPresenter extends BasePresenter
{

	/**
	 * @var Github\Client
	 * @inject
	 */
	public $ghClient;

	public function renderPull($issueId, $path)
	{
		$api = $this->ghClient->api('pull_requests');
		$pr = $api->show('nette', 'web-content', $issueId);
		$files = $api->files('nette', 'web-content', $issueId);

		if ($path !== NULL) {
			foreach ($files as $file) {
				if ($file['filename'] === $path) {
					$texyContent = file_get_contents($file['raw_url']);
					break;
				}
			}

			if (!isset($texyContent)) $this->error();
			$this->forward(new Nette\Application\Request(
				'Editor',
				'POST',
				['action' => 'default', 'branch' => $pr['base']['ref'], 'path' => $path],
				['texyContent' => $texyContent]
			));
		}

		$this->template->pr = $pr;
		$this->template->files = $files;
	}

}
