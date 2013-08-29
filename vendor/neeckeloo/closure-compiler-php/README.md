ClosureCompilerPHP
==================

The Closure Compiler compiles JavaScript into compact, high-performance code.

[![Build Status](https://secure.travis-ci.org/neeckeloo/ClosureCompilerPHP.png?branch=master)](http://travis-ci.org/neeckeloo/ClosureCompilerPHP)

Requirements
------------

ClosureCompilerPHP works with PHP 5.3 or later.

Usage
-----

### Basic usage

```php
<?php
$compiler = new RemoteCompiler();
$compiler->addScript('var a = "hello"; alert(a);');

$response = $compiler->compile();
$compiledCode = $response->getCompiledCode();
```

### File compilation

```php
<?php
$compiler = new RemoteCompiler();
$compiler->addLocalFile(__DIR__ . '/script.js');

$response = $compiler->compile();
$compiledCode = $response->getCompiledCode();
```

Running tests
-------------

The tests use PHPUnit

Closure compiler original documentation
-------------------------------

[https://developers.google.com/closure/compiler/docs/api-ref](https://developers.google.com/closure/compiler/docs/api-ref)