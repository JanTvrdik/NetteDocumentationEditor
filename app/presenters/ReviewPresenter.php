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
		try {
			$gh = $this->context->parameters['github'];
			$api = $this->ghClient->api('pull_requests');
			$pr = $api->show($gh['repoOwner'], $gh['repoName'], $issueId);
			$files = $api->files($gh['repoOwner'], $gh['repoName'], $issueId);

			if ($path !== NULL) {
				foreach ($files as $file) {
					if ($file['filename'] === $path) {
						$texyContent = file_get_contents($file['raw_url']);
						break;
					}
				}
				if (!isset($texyContent)) $this->error();

				$commits = $api->commits($gh['repoOwner'], $gh['repoName'], $issueId);
				if (!isset($commits[0]['commit']['author'])) $this->error();
				$author = $commits[0]['commit']['author'];

				$this->forward(new Nette\Application\Request(
					'Editor',
					'POST',
					['action' => 'default', 'branch' => $pr['base']['ref'], 'path' => $path],
					['texyContent' => $texyContent, 'authorName' => $author['name'], 'authorEmail' => $author['email']]
				));
			}

			$this->template->pr = $pr;
			$this->template->files = $files;

		} catch (Github\Exception\RuntimeException $e) {
			if ($e->getCode() === 404) $this->error();
			else throw $e;
		}
	}

}
