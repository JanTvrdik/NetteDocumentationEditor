<?php
namespace App;

use Nette;
use Nette\Utils\Strings;


class EditorLocalModel extends Nette\Object implements IEditorModel
{

	/** @var string */
	private $dir;

	/** @var array */
	private $env;

	/** @var string */
	private $activeBranch;

	public function __construct($directory, $env)
	{
		$this->dir = $directory;
		$this->env = $env;
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
		return ($this->loadPage($branch, $path)) !== NULL;
	}

	/**
	 * Checks whether given user has permission to edit the repository.
	 *
	 * @param  string
	 * @return bool
	 */
	public function canEdit($username)
	{
		return TRUE;
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
			$content = $this->exec('show', ["$branch:$path"]);

		} catch (IOException $e) {
			return NULL;
		}

		$page = new RepoPage();
		$page->branch = $branch;
		$page->path = $path;
		$page->prevBlobHash = sha1($content);
		$page->content = $content;

		return $page;
	}

	/**
	 * Saves page to the repository.
	 *
	 * @param  RepoPage
	 * @param  string  user access token with user:email permission
	 * @return bool
	 */
	public function savePage(RepoPage $page, $userAccessToken = NULL)
	{
		$this->smartCheckout($page->branch);

		// whitespace correction
		$page->content = trim($page->content) . "\n";
		$page->content = Strings::replace($page->content, '#\h+$#m', '');

		$this->exec('add', [$this->dir . '/' . $page->path]);
		$this->exec('commit', ['message' => $page->message]); // TODO: check $page->prevBlobHash
		return TRUE;
	}

	/**
	 * Saves page draft.
	 *
	 * @param  RepoPage $page
	 * @return void
	 */
	public function savePageDraft(RepoPage $page)
	{
		$this->smartCheckout($page->branch);
		file_put_contents($this->dir . '/' . $page->path, $page->content, LOCK_EX); // TODO: check $page->prevBlobHash
		return TRUE;
	}

	/**
	 * Acquire user access token.
	 *
	 * @param  string temporary code provided by GitHub (see http://developer.github.com/v3/oauth/#github-redirects-back-to-your-site)
	 * @return string user access token
	 */
	public function getAccessToken($code)
	{
		return NULL;
	}

	/**
	 * @param  string      $branch
	 * @param  string      $path
	 * @return string|NULL binary data
	 */
	public function loadImage($branch, $path)
	{
		try {
			return $this->exec('show', ["$branch:$path"]);

		} catch (IOException $e) {
			return NULL;
		}
	}

	/**
	 * Executes a Git command.
	 *
	 * @param    string
	 * @param    array|NULL
	 * @param    string|NULL
	 * @return   string
	 * @throws   IOException
	 */
	private function exec($command, $args = NULL, $stdin = NULL)
	{
		$command = 'git ' . $this->buildCommand($command, $args);

		$process = proc_open(
			$command,
			[
				0 => ['pipe', 'r'], // stdin
				1 => ['pipe', 'w'], // stdout
				2 => ['pipe', 'w'], // stderr
			],
			$pipes,
			$this->dir,
			$this->env
		);

		if ($process === FALSE) {
			throw new IOException();
		}

		if ($stdin) {
			fwrite($pipes[0], $stdin);
			fclose($pipes[0]);
		}

		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);

		if (($exitCode = proc_close($process)) !== 0) {
			throw new IOException("Process exited with code '$exitCode' ($stderr).", $exitCode);
		}

		return $stdout;
	}

	/**
	 * Builds a command.
	 *
	 * @param    string for example "log"
	 * @param    array|NULL argName => TRUE|FALSE|argValue
	 * @return   string
	 */
	private function buildCommand($command, $args = NULL)
	{
		if ($args) {
			foreach ($args as $arg => $value) {
				if (is_int($arg)) {
					$command .= ' ' . escapeshellarg($value);
				} elseif (is_bool($value)) {
					if ($value) {
						$command .= ' --' . $arg;
					}
				} else {
					$command .= ' --' . $arg . '=' . escapeshellarg($value);
				}
			}
		}

		return $command;
	}

	/**
	 * @param  string $branch
	 * @return void
	 */
	private function smartCheckout($branch)
	{
		if ($this->activeBranch === NULL) {
			$this->activeBranch = trim($this->exec('rev-parse', ['abbrev-ref' => TRUE, 'HEAD']));
		}

		if ($branch !== $this->activeBranch) {
			if (trim($this->exec('status', ['short' => TRUE])) !== '') {
				$this->exec('add', ['all' => TRUE]);
				$this->exec('commit', ['message' => 'WIP']);
			}
			$this->exec('checkout', [$branch]);
			$this->activeBranch = $branch;
		}
	}

}
