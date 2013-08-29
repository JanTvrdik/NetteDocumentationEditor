<?php

namespace WebLoader;

/**
 * FileCollection
 *
 * @author Jan Marek
 * @author Mgr. Martin Jantošovič <martin.jantosovic@freya.sk>
 */
class FileCollection implements IFileCollection
{
	/**
	 * List of tested suffixes if the file doesn't exist.
	 * E.g. $suffixes = [ "js", "less" ]
	 * 	if addFile is called with 'test' and this file doesn't exist,
	 * 	but the 'test.js' exists, it will be added
	 *
	 * @var array
	 */
	private $suffixes = array();

	/** @var string */
	private $root;

	/** @var array */
	private $files = array();

	/** @var array */
	private $remoteFiles = array();

	/**
	 * @param string|null $root files root for relative paths
	 * @param string|array $suffixes Searched suffixes
	 */
	public function __construct($root = NULL, $suffixes = [])
	{
		$this->root = $root;
		$this->setSuffixes($suffixes);
	}

	/**
	 * Set suffixes
	 * return $this
	 */
	public function setSuffixes($values) {
		if (is_array($values))
			$this->suffixes = $values;
		else
			$this->suffixes = [ $values ];
		return $this;
	}

	/**
	 * Get searched suffixes
	 * return array
	 */
	public function getSuffixes() {
		return $this->suffixes;
	}

	/**
	 * Get file list
	 * @return array
	 */
	public function getFiles()
	{
		return array_values($this->files);
	}

	/**
	 * Make path absolute
	 * @param $path string
	 * @throws \WebLoader\FileNotFoundException
	 * @return string
	 */
	public function cannonicalizePath($path, $need = TRUE)
	{
		if (is_array($this->root)) {
			foreach ($this->root as $root) {
				$rel = Path::normalize($root . "/" . $path);
				if ($rel !== false && is_file($rel)) return $rel;
				foreach ($this->suffixes as $suffix) {
					$rel = Path::normalize($root . "/" . $path . '.' . $suffix);
					if ($rel !== false && is_file($rel)) return $rel;
				}
			}
		} else {
			$rel = Path::normalize($this->root . "/" . $path);
			if ($rel !== false && is_file($rel)) return $rel;
			foreach ($this->suffixes as $suffix) {
				$rel = Path::normalize($this->root . "/" . $path . '.' . $suffix);
				if ($rel !== false && is_file($rel)) return $rel;
			}
		}

		$abs = Path::normalize($path);
		if ($abs !== false && is_file($abs)) return $abs;
		foreach ($this->suffixes as $suffix) {
			$abs = Path::normalize($path . '.' . $suffix);
			if ($abs !== false && is_file($abs)) return $abs;
		}

		if ($need)
			throw new FileNotFoundException("File '$path' does not exist.");
		else
			return FALSE;
	}


	/**
	 * Add file
	 * @param $file string filename
	 */
	public function addFile($file, $need = TRUE, $prepend = FALSE)
	{
		$file = $this->cannonicalizePath((string) $file, $need);

		if (!$need && $file === FALSE)
			return;

		if (in_array($file, $this->files)) {
			return;
		}

		if ($prepend)
			array_unshift($this->files, $file);
		else
			$this->files[] = $file;
	}


	/**
	 * Add files
	 * @param array|\Traversable $files array list of files
	 */
	public function addFiles($files)
	{
		foreach ($files as $file) {
			$this->addFile($file);
		}
	}


	/**
	 * Remove file
	 * @param $file string filename
	 */
	public function removeFile($file)
	{
		$this->removeFiles(array($file));
	}


	/**
	 * Remove files
	 * @param array $files list of files
	 */
	public function removeFiles(array $files)
	{
		$files = array_map(array($this, 'cannonicalizePath'), $files);
		$this->files = array_diff($this->files, $files);
	}


	/**
	 * Add file in remote repository (for example Google CDN).
	 * @param string $file URL address
	 */
	public function addRemoteFile($file)
	{
		if (in_array($file, $this->remoteFiles)) {
			return;
		}

		$this->remoteFiles[] = $file;
	}

	/**
	 * Add multiple remote files
	 * @param array|\Traversable $files
	 */
	public function addRemoteFiles($files)
	{
		foreach ($files as $file) {
			$this->addRemoteFile($file);
		}
	}

	/**
	 * Remove all files
	 */
	public function clear()
	{
		$this->files = array();
		$this->remoteFiles = array();
	}

	/**
	 * @return array
	 */
	public function getRemoteFiles()
	{
		return $this->remoteFiles;
	}

	/**
	 * @return string
	 */
	public function getRoot()
	{
		return $this->root;
	}

	/**
	 * Allow change root directory
	 * @param string $root Root directory
	 */
	public function setRoot($root) {
		$this->root = [];
		if (is_array($root)) {
			foreach ($root as $dir) {
				$this->addRoot($dir);
			}
		} else {
			$this->addRoot($root);
		}
	}

	/**
	 * Add fallback root
	 * @param string $folder
	 */
	public function addRoot($folder) {
		if (!is_array($this->root))
			$this->root = [ $this->root ];

		if (is_dir($folder))
			$this->root[] = $folder;
		else
			throw new FileNotFoundException("Directory '$folder' does not exist.");
	}

}
