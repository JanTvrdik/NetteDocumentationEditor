<?php

namespace WebLoader\Nette\Diagnostics;

use Nette\Utils\Strings;

final class Panel implements \Nette\Diagnostics\IBarPanel {

	public static $icon = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAJ+SURBVBgZBcExbFRlAADg7//fu7teC3elQEoMgeDkYDQ6oMQQTYyGxMHZuDA6Ypw0cWI20cHJUdl0cJLIiomR6OACGhUCpqGWtlzbu/b97/3v9/tCKQVc/e7RRXz+7OrSpUXbW7S9tu8ddv0M+3iCjF1s42v8WAP0XffKi2eOXfro9dMAYJ766SL1092jfDa17DfZgycHfvh7/hau1QB9161PhgE8epoNQlAHqprRIDo3iqoYDSpeOjv2zHRl7atfNj6LALltJys1Xc9+CmYtTxtmR8yO2D7kv4MMPr7x0KULK54/NThdA+S2XTs+jOYN86MsxqBGVRErKkEV6BHynp//2fXbw9lGDZBTWp+OK7PDzqIpYiyqSMxBFakUVYVS2dxrfHHrrz1crQG6lM6vTwZmR0UHhSoHsSBTKeoS9YU8yLrUXfj+w9d2IkBOzfkz05F5KkKkCkFERACEQil0TSOnJkMNV67fHNdVHI4GUcpZVFAUZAEExEibs4P5osMeROiadHoUiIEeCgFREAoRBOMB2weNrkmbNz+9UiBCTs1yrVdHqhgIkRL0EOj7QGG5jrZ2D+XUbADEy9dunOpSun7xuXMe7xUPNrOd/WyeyKUIoRgOGS8xWWZ7b6FLaROgzim9iXd+vXvf7mHtoCnaXDRtkLpel3t9KdamUx+8fcbj7YWc0hZAndv25XffeGH8yfuvAoBcaHOROhS+vLlhecD+wUJu222AOrft/cdPZr65ddfqsbHVyZLVlZHpysjx5aHRMBrV0XuX141qtnb25bb9F6Duu+7b23funb195955nMRJnMAJTJeGg8HS0sBkZWx1suz3Px79iZ8A/gd7ijssEaZF9QAAAABJRU5ErkJggg==";

	private static $files = [];

	public static function register() {
		\Nette\Diagnostics\Debugger::addPanel(new self());
	}

	public static function addFile($source, $generated, $memory = NULL) {
		if (is_array($source)) {
			foreach ($source as $file) {
				self::$files[$file] = [
					'name' => $generated,
					'memory' => $memory
				];
			}
		} else
			self::$files[$source] = [
				'name' => $generated,
				'memory' => $memory
			];
	}

	private static function link($file) {
		//$link = 'editor://open/?file=' . urlencode($file) . '&line=0';
		$link = str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', $file);
		$name = str_replace(WWW_DIR, '', $file);
		$ret = '<a href="'.$link.'" target="_blank">';
		$ret .= $name;
		$ret .= "</a>";
		return $ret;
	}

	/*** IDebugPanel ***/

	public function getTab() {
		$sum = count(self::$files);
		return '<span><img src="' . self::$icon . '">WebLoader ('.$sum.')</span>';
	}

	public function getPanel() {
		$sum = count(self::$files);
		$buff = '<h1>WebLoader</h1>';
		$buff .= '<div class="nette-inner">';
		$buff .= '<table>';
		$buff .= '<thead><tr><th>Source</th><th>Generated file</th><th>Memory usage</th></tr></thead>';
		$i = 0;
		foreach (self::$files as $source => $generated) {
			$buff .= "<tr><th" . ($i%2 ? 'class="nette-alt"' : ''). ">"
				. self::link($source)
				. "</th><td>"
				. self::link($generated['name'])
				. "</td><td>"
				. \Nette\Templating\Helpers::bytes($generated['memory'])
				. "</td></tr>";
		}
		$buff .= '</table>';
		$buff .= '</div>';
		return $buff;
	}

	public function getId()
	{
		return 'WebLoader';
	}
}
