<?php
namespace Test;

use App\EditorModel;
use Nette;
use Tester;
use Tester\Assert;

$dic = require __DIR__ . '/bootstrap.php';

test(function () {
	$dataProvider = [
		'http://doc.nette.org/cs/components' => ['doc-2.0', 'cs/components.texy'],
		'http://doc.nette.org/cs/' => ['doc-2.0', 'cs/homepage.texy'],
		'http://doc09.nette.org/cs/autentizace' => ['doc-0.9', 'cs/autentizace.texy'],
		'http://nette.org/en/download' => ['www', 'en/download.texy'],
	];

	$model = new EditorModel();
	foreach ($dataProvider as $url => $expected) {
		Assert::same( $expected, $model->urlToRepoPath($url) );
	}
});


test(function () {
	$model = new EditorModel();
	Assert::true( gettype($model->loadPageContent('doc-2.0', 'cs/components.texy')) === 'string' );
});

