<?php
namespace Test;

use App\WebRepoMapper;
use Nette;
use Tester;
use Tester\Assert;

$dic = require __DIR__ . '/bootstrap.php';



test(function () {
	$mapper = new WebRepoMapper();
	Assert::same( ['doc09', 'cs', 'quickstart/model'], $mapper->repoToWeb('doc-0.9', 'cs/quickstart/model.texy') );
	Assert::false( $mapper->repoToWeb('doc-0.9', 'quickstart/model.texy') );
	Assert::false( $mapper->repoToWeb('doc-0.9', 'cs/quickstart/model') );
});

test(function () {
	$mapper = new WebRepoMapper();
	Assert::same( ['doc-0.9', 'cs/quickstart/model.texy'], $mapper->webToRepo('doc09', 'cs', 'quickstart/model') );
});

test(function () {
	$dataProvider = [
		'http://doc.nette.org/cs/components#fragment' => ['doc-2.0', 'cs/components.texy'],
		'http://doc.nette.org/cs' => ['doc-2.0', 'cs/homepage.texy'],
		'http://doc.nette.org/cs/' => ['doc-2.0', 'cs/homepage.texy'],
		'http://doc09.nette.org/cs/autentizace' => ['doc-0.9', 'cs/autentizace.texy'],
		'http://nette.org/en/download' => ['www', 'en/download.texy'],
		'nette.org/en/download' => ['www', 'en/download.texy'],
		'nette.org/download' => ['www', 'en/download.texy'],
		'nette.org/download/' => ['www', 'en/download.texy'],
		'nette.org' => ['www', 'en/homepage.texy'],
		'nette.org/' => ['www', 'en/homepage.texy'],
		'example.com' => FALSE,
	];

	$mapper = new WebRepoMapper();
	foreach ($dataProvider as $url => $expected) {
		Assert::same( $expected, $mapper->urlToRepo($url) );
	}
});
