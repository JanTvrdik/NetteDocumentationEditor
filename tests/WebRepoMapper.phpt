<?php
namespace Test;

use App\WebRepoMapper;
use Nette;
use Tester;
use Tester\Assert;

$dic = require __DIR__ . '/bootstrap.php';



test(function () {
	$mapper = new WebRepoMapper();
	Assert::same( ['www', 'en', 'about'], $mapper->repoToWeb('nette.org', 'www/en/about.texy') );
	Assert::same( ['doc-0.9', 'cs', 'quickstart/model'], $mapper->repoToWeb('doc-0.9', 'cs/quickstart/model.texy') );
	Assert::false( $mapper->repoToWeb('doc-0.9', 'quickstart/model.texy') );
	Assert::false( $mapper->repoToWeb('doc-0.9', 'cs/quickstart/model') );
});

test(function () {
	$mapper = new WebRepoMapper();
	Assert::same( ['doc-0.9', 'cs/quickstart/model.texy'], $mapper->webToRepo('doc-0.9', 'cs', 'quickstart/model') );
	Assert::same( ['doc-2.1', 'cs/homepage.texy'], $mapper->webToRepo('doc', 'cs', 'homepage') );
});

test(function () {
	$mapper = new WebRepoMapper();
	Assert::same( 'http://doc.nette.org/cs/0.9/quickstart/model', $mapper->webToUrl('doc-0.9', 'cs', 'quickstart/model') );
	Assert::same( 'http://pla.nette.org/en/', $mapper->webToUrl('pla', 'en', 'homepage') );
	Assert::same( 'http://nette.org/cs/about', $mapper->webToUrl('www', 'cs', 'about') );
	Assert::same( 'http://nette.org/cs/', $mapper->webToUrl('www', 'cs', 'homepage') );
});

test(function () {
	$dataProvider = [
		'http://doc.nette.org/cs/2.0/components#fragment' => ['doc-2.0', 'cs/components.texy'],
		'http://doc.nette.org/cs/0.9/autentizace' => ['doc-0.9', 'cs/autentizace.texy'],
		'http://doc.nette.org/cs/2.0/' => ['doc-2.0', 'cs/homepage.texy'],
		'http://doc.nette.org/cs/2.0' => ['doc-2.0', 'cs/homepage.texy'],
		'http://doc.nette.org/cs/' => ['doc-2.1', 'cs/homepage.texy'],
		'http://doc.nette.org/' => ['doc-2.1', 'en/homepage.texy'],
		'http://nette.org/en/download' => ['nette.org', 'www/en/download.texy'],
		'nette.org/en/download' => ['nette.org', 'www/en/download.texy'],
		'nette.org/download' => ['nette.org', 'www/en/download.texy'],
		'nette.org/download/' => ['nette.org', 'www/en/download.texy'],
		'nette.org' => ['nette.org', 'www/en/homepage.texy'],
		'nette.org/' => ['nette.org', 'www/en/homepage.texy'],
		'example.com' => FALSE,
	];

	$mapper = new WebRepoMapper();
	foreach ($dataProvider as $url => $expected) {
		Assert::same( $expected, $mapper->urlToRepo($url) );
	}
});
