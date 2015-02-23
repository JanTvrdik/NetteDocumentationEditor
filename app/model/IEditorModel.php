<?php
namespace App;

use Github;

interface IEditorModel
{

	/**
	 * Checks whether page exists.
	 *
	 * @param  string
	 * @param  string
	 * @return bool
	 */
	public function pageExists($branch, $path);

	/**
	 * Checks whether given user has permission to edit the repository.
	 *
	 * @param  string
	 * @return bool
	 */
	public function canEdit($username);

	/**
	 * Returns content of given page in Texy! formatting.
	 *
	 * @param  string $branch
	 * @param  string $path
	 * @return RepoPage|NULL
	 * @throws NotSupportedException
	 * @throws InvalidArgumentException if URL is not on nette.org domain
	 */
	public function loadPage($branch, $path);

	/**
	 * Saves page to the repository.
	 *
	 * @param  RepoPage
	 * @param  string  user access token with user:email permission
	 * @return Github\HttpClient\Message\Response
	 * @throws PermissionDeniedException
	 * @throws PageSaveConflictException
	 */
	public function savePage(RepoPage $page, $userAccessToken);

	/**
	 * Saves page draft.
	 *
	 * @param  RepoPage $page
	 * @return void
	 */
	public function savePageDraft(RepoPage $page);

	/**
	 * Acquire user access token.
	 *
	 * @param  string temporary code provided by GitHub (see http://developer.github.com/v3/oauth/#github-redirects-back-to-your-site)
	 * @return string user access token
	 */
	public function getAccessToken($code);

	/**
	 * @param  string      $branch
	 * @param  string      $path
	 * @return string|NULL binary data
	 */
	public function loadImage($branch, $path);

}
