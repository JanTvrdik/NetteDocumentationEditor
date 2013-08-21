<?php
namespace App;

use Github;
use Nette;
use Nette\Utils\Strings;


class EditorModel extends Nette\Object
{

	/** @var Github\Client */
	private $ghClient;

	public function __construct(Github\Client $ghClient)
	{
		$this->ghClient = $ghClient;
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
			$response = $this->ghClient->getHttpClient()->request(
				'repos/nette/web-content/contents/' . str_replace('%2F', '/', urlencode($path)),
				array(),
				'HEAD'
			);

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
		// TODO: use values form config
		return $this->ghClient->api('repos')->contents()->show('nette', 'web-content', $path, $branch);
	}

	/**
	 * Converts absolute URL (on nette.org domain) to branch and path inside Git repository.
	 *
	 * @param  string $url
	 * @return array  0 => branch, 1 => path
	 * @throws InvalidArgumentException if URL is not on nette.org domain
	 */
	public function urlToRepoPath($url)
	{
		$url = new Nette\Http\Url($url);
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
