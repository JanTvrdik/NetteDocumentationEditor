<?php
namespace App;

use Nette;


class RepoPage extends Nette\Object
{

	/** @var string branch to which this page belong */
	public $branch;

	/** @var string path to this page in repository */
	public $path;

	/** @var string hash of commit which this page's content is based on; used to prevent overwriting work of someone else */
	public $parentCommit;

	/** @var string blob hash of the file this page is based on */
	public $prevBlobHash;

	/** @var string page content in Texy! format */
	public $content;

	/** @var string short message describing the change; used as commit message */
	public $message;

	/** @var string */
	public $authorName;

	/** @var string */
	public $authorEmail;

}
