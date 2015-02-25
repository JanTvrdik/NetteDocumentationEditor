<?php
namespace App;

use Github;
use Nette;
use Nette\Utils\Strings;

class EditorModel extends Nette\Object implements IEditorModel
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
			$this->ghClient->api('repos')->collaborators()->check($this->repoOwner, $this->repoName, $username);
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
	 * @return RepoPage|NULL
	 * @throws NotSupportedException
	 * @throws InvalidArgumentException if URL is not on nette.org domain
	 */
	public function loadPage($branch, $path)
	{
		try {
			$file = $this->ghClient->api('repos')->contents()->show($this->repoOwner, $this->repoName, $path, $branch);

		} catch (Github\Exception\RuntimeException $e) {
			if ($e->getCode() === 404) return NULL;
			throw $e;
		}

		if (!isset($file['path'], $file['sha'], $file['content'])) {
			return NULL;
		}

		$page = new RepoPage();
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
	 * @throws PageSaveConflictException
	 */
	public function savePage(RepoPage $page, $userAccessToken)
	{
		$user = $this->getUser($userAccessToken);
		if (!$this->canEdit($user['login'])) throw new PermissionDeniedException();

		$httpClient = $this->ghClient->getHttpClient();
		$httpClient->authenticate($this->accessToken, NULL, Github\Client::AUTH_HTTP_TOKEN);

		// whitespace correction
		$page->content = trim($page->content) . "\n";
		$page->content = Strings::replace($page->content, '#\h+$#m', '');

		try {
			$response = $httpClient->put(
				$this->getRepoPath() . '/contents/' . urlencode($page->path), [
					'message' => $page->message,
					'content' => base64_encode($page->content),
					'sha' => $page->prevBlobHash,
					'branch' => $page->branch,
					'committer' => [
						'name' => $user['login'],
						'email' => $user['email'],
					],
					'author' => [
						'name' => $page->authorName ?: $user['login'],
						'email' => $page->authorEmail ?: $user['email'],
					],
				]
			);

		} catch (Github\Exception\RuntimeException $e) {
			if ($e->getCode() === 409) throw new PageSaveConflictException($e->getMessage(), NULL, $e);
			throw $e;
		}

		return $response;
	}

	/**
	 * Saves page draft.
	 *
	 * @param  RepoPage $page
	 * @return void
	 */
	public function savePageDraft(RepoPage $page)
	{
		// TODO: Implement savePageDraft() method.
	}

	/**
	 * Acquire user access token.
	 *
	 * @param  string temporary code provided by GitHub (see http://developer.github.com/v3/oauth/#github-redirects-back-to-your-site)
	 * @return string user access token
	 */
	public function getAccessToken($code)
	{
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
	 * @param  string      $branch
	 * @param  string      $path
	 * @return string|NULL binary data
	 */
	public function loadImage($branch, $path)
	{
		return @file_get_contents("https://raw.github.com/nette/web-content/$branch/$path") ?: NULL;
	}

	/**
	 * @param  string   $branch
	 * @return string[]
	 */
	public function getPages($branch)
	{
		try {
			$response = $this->ghClient->api('git')->trees()->show($this->repoOwner, $this->repoName, $branch, TRUE);

		} catch (Github\Exception\RuntimeException $e) {
			return [];
		}

		$pages = [];
		foreach ($response['tree'] as $file) {
			if (substr($file['path'], -5) === '.texy') {
				$pages[] = $file['path'];
			}
		}
		return $pages;
	}

	/**
	 * @return string[]
	 */
	public function getBranches()
	{
		return array_map(
			function ($branch) {
				return $branch['name'];
			},
			$this->ghClient->api('repo')->branches($this->repoOwner, $this->repoName)
		);
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

		if (empty($user['email'])) {
			$mails = $currentUser->emails()->all();
			$user['email'] = reset($mails); // pick the first email
		}

		return $user;
	}

}
