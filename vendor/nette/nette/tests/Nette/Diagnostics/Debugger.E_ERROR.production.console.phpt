<?php

/**
 * Test: Nette\Diagnostics\Debugger E_ERROR in production & console mode.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



Debugger::$productionMode = TRUE;
header('Content-Type: text/plain');

Debugger::enable();

Debugger::$onFatalError[] = function() {
	Assert::match('ERROR:%A%', ob_get_clean());
	die(0);
};
ob_start();


missing_funcion();
