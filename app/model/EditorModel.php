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
	private $clientId;

	/** @var string */
	private $clientSecret;

	/** @var string */
	private $repoOwner;

	/** @var string */
	private $repoName;

	/** @var string */
	private $accessToken;

	public function __construct(Github\Client $ghClient, $clientId, $clientSecret, $repoOwner, $repoName, $accessToken)
	{
		$this->ghClient = $ghClient;
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
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
			$apiPath = $this->getRepoPath() . '/contents/' . urlencode($path) . '?ref=' . urlencode($branch);
			$response = $this->ghClient->getHttpClient()->request($apiPath, [], 'HEAD');

		} catch (Github\Exception\RuntimeException $e) {
			if ($e->getCode() === 404) return FALSE;
			throw $e;
		}

		return $response->getStatusCode() === 200;
	}

	/**
	 * Checks whether given user has permission to edit the repository.
	 *
	 * @param  string
	 * @return bool
	 */
	public function canEdit($username)
	{
		try {
			$response = $this->ghClient->api('repos')->collaborators()->check($this->repoOwner, $this->repoName, $username);
			return TRUE;

		} catch (Github\Exception\RuntimeException $e) {
			if ($e->getCode() === 404) return FALSE;
			throw $e;
		}
	}

	/**
	 * Returns content of given page in Texy! formatting.
	 *
	 * @param  string $branch
	 * @param  string $path
	 * @return Page|NULL
	 * @throws NotSupportedException
	 * @throws InvalidArgumentException if URL is not on nette.org domain
	 */
	public function loadPage($branch, $path)
	{
		try {
			$file = $this->ghClient->api('repos')->contents()->show($this->repoOwner, $this->repoName, $path, $branch);

		} catch (\Github\Exception\RuntimeException $e) {
			if ($e->getCode() === 404) return NULL;
			throw $e;
		}

		$page = new Page();
		$page->branch = $branch;
		$page->path = $file['path'];
		$page->prevBlobHash = $file['sha'];
		$page->content = base64_decode($file['content']);

		return $page;
	}

	/**
	 * Saves page to the repository.
	 *
	 * @param  Page
	 * @param  string  user access token with user:email permission
	 * @return Github\HttpClient\Message\Response
	 * @throws PermissionDeniedException
	 */
	public function savePage(Page $page, $userAccessToken)
	{
		$user = $this->getUser($userAccessToken);
		if (!$this->canEdit($user['login'])) throw new PermissionDeniedException();

		$httpClient = $this->ghClient->getHttpClient();
		$httpClient->authenticate($this->accessToken, NULL, Github\Client::AUTH_HTTP_TOKEN);
		$response = $httpClient->put(
			$this->getRepoPath() . '/contents/' . urlencode($page->path), [
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
	 * Acquire user access token.
	 *
	 * @param  string temporary code provided by GitHub (see http://developer.github.com/v3/oauth/#github-redirects-back-to-your-site)
	 * @return string user access token
	 */
	public function getAccessToken($code)
	{
		$ghParams = $this->context->parameters['github'];
		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => [
					'Content-Type: application/json',
					'Accept: application/json'
				],
				'content' => Nette\Utils\Json::encode([
					'client_id' => $this->clientId,
					'client_secret' => $this->clientSecret,
					'code' => $code,
				]),
			]
		]);

		$json = file_get_contents('https://github.com/login/oauth/access_token', NULL, $context);
		$params = Nette\Utils\Json::decode($json, Nette\Utils\Json::FORCE_ARRAY);
		return isset($params['access_token']) ? $params['access_token'] : FALSE;
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
			if (!Strings::startsWith($url, 'http://')) $url = "http://$url";
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

	/**
	 * @returns string
	 */
	private function getRepoPath()
	{
		return sprintf('repos/%s/%s', urlencode($this->repoOwner), urlencode($this->repoName));
	}

	/**
	 * @param  string access token with user:email permission
	 * @return array
	 */
	private function getUser($accessToken)
	{
		$this->ghClient->authenticate($accessToken, Github\Client::AUTH_HTTP_TOKEN);
		$currentUser = $this->ghClient->api('current_user');
		$user = $currentUser->show();

		if ($user['email'] === NULL) {
			$mails = $currentUser->emails()->all();
			$user['email'] = reset($mails); // pick the first email
		}

		return $user;
	}

}
