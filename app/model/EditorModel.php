<?php
namespace App;

use Github;
use Nette;
use Nette\Utils\Strings;


class EditorModel extends Nette\Object
{

	/** @var Github\Client */
	private $ghClient;

	/** @var string */
	private $repoOwner;

	/** @var string */
	private $repoName;

	/** @var string */
	private $accessToken;

	public function __construct(Github\Client $ghClient, $repoOwner, $repoName, $accessToken)
	{
		$this->ghClient = $ghClient;
		$this->repoOwner = $repoOwner;
		$this->repoName = $repoName;
		$this->accessToken = $accessToken;
	}

	/**
	 * Checks whether page exists.
	 *
	 * @param  string
	 * @param  string
	 * @return bool
	 */
	public function pageExists($branch, $path)
	{
		try {
			$httpClient = $this->ghClient->getHttpClient();
			$apiPath = sprintf(
				'repos/%s/%s/contents/%s?ref=%s',
				urlencode($this->repoOwner), urlencode($this->repoName), urlencode($path), urlencode($branch)
			);
			$response = $httpClient->request($apiPath, [], 'HEAD');

		} catch (Github\Exception\RuntimeException $e) {
			if ($e->getCode() === 404) return FALSE;
			throw $e;
		}

		return $response->getStatusCode() === 200;
	}

	/**
	 * Returns content of given page in Texy! formatting.
	 *
	 * @param  string $branch
	 * @param  string $path
	 * @return string
	 * @throws NotSupportedException
	 * @throws InvalidArgumentException if URL is not on nette.org domain
	 */
	public function loadPage($branch, $path)
	{
		return $this->ghClient->api('repos')->contents()->show($this->repoOwner, $this->repoName, $path, $branch);
	}

	/**
	 * Saves page to the repository.
	 *
	 * @param  Page
	 * @param  string  user access token with user:email permission
	 * @return Github\HttpClient\Message\Response
	 */
	public function savePage(Page $page, $userAccessToken)
	{
		$this->ghClient->authenticate($userAccessToken, Github\Client::AUTH_HTTP_TOKEN);
		$currentUser = $this->ghClient->api('current_user');
		$user = $currentUser->show();

		if ($user['email'] === NULL) {
			$mails = $currentUser->emails()->all();
			$user['email'] = reset($mails);
		}


		$this->ghClient->authenticate($this->accessToken, NULL, Github\Client::AUTH_HTTP_TOKEN);
		$response = $this->ghClient->getHttpClient()->put(
			sprintf(
				'repos/%s/%s/contents/%s',
				urlencode($this->repoOwner), urlencode($this->repoName), urlencode($page->path)
			), [
				'message' => $page->message,
				'content' => base64_encode($page->content),
				'sha' => $page->prevBlobHash,
				'branch' => $page->branch,
				'author.name' => $user['name'],
				'author.email' => $user['email'],
			]
		);

		return $response;
	}

	/**
	 * Converts absolute URL (on nette.org domain) to branch and path inside Git repository.
	 *
	 * @param  string $url
	 * @return array  0 => branch, 1 => path
	 * @throws InvalidArgumentException if URL is not on nette.org domain or is invalid
	 */
	public function urlToRepoPath($url)
	{
		try {
			$url = new Nette\Http\Url($url);
		} catch (Nette\InvalidArgumentException $e) {
			throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
		}
		if (!Strings::endsWith($url->host, 'nette.org')) throw new InvalidArgumentException();
		$hostParts = explode('.', $url->host);
		if (count($hostParts) === 2) $subdomain = 'www';
		else $subdomain = $hostParts[0];

		$aliases = ['doc' => 'doc-2.0', 'doc09' => 'doc-0.9'];
		$branch = isset($aliases[$subdomain]) ? $aliases[$subdomain] : $subdomain;

		$path = trim($url->path, '/');
		if (substr_count($path, '/') === 0) $path .= '/homepage';
		$path .= '.texy';

		return [$branch, $path];
	}

}
