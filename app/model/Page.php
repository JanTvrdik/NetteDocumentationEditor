<?php
namespace App;

use Nette;
use Nette\Utils\Strings;


/**
 * @propety-read string $book
 * @propety-read string $lang
 * @propety-read string $name
 */
class Page extends Nette\Object
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

	public function getBook()
	{
		$aliases = ['doc-2.0' => 'doc', 'doc-0.9' => 'doc09'];
		return isset($aliases[$this->branch]) ? $aliases[$this->branch] : $this->branch;
	}

	public function getLang()
	{
		if ($m = Strings::match($this->path, '#^([a-z]{2})/#')) {
			return $m[1];
		} else {
			return 'xx'; // unknown
		}
	}

	public function getName()
	{
		if ($m = Strings::match($this->path, '#^([a-z]{2})/#')) {
			return Strings::substring($this->path, 2);
		} else {
			return $this->path; // unknown
		}
	}

}
