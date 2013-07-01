<?php
namespace App;

use Nette;
use Nette\Utils\Strings;


class EditorModel extends Nette\Object
{

	/**
	 * Returns content of given page in Texy! formatting.
	 *
	 * @param  string $branch
	 * @param  string $path
	 * @return string
	 * @throws NotSupportedException
	 * @throws InvalidArgumentException if URL is not on nette.org domain
	 */
	public function loadPageContent($branch, $path)
	{
		$client = new \Github\Client();
		$file = $client->api('repos')->contents()->show('nette', 'web-content', $path, $branch);
		if ($file['encoding'] !== 'base64') throw new NotSupportedException();
		return base64_decode($file['content']);
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
