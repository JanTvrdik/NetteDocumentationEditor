<?php

/**
 * Test: Nette\Diagnostics\Debugger error logging.
 *
 * @author     David Grudl
 * @package    Nette\Diagnostics
 */

use Nette\Diagnostics\Debugger;



require __DIR__ . '/../bootstrap.php';



// Setup environment
$_SERVER['HTTP_HOST'] = 'nette.org';

Debugger::$logDirectory = TEMP_DIR . '/log';
Tester\Helpers::purge(Debugger::$logDirectory);

Debugger::$mailer = 'testMailer';

Debugger::enable(Debugger::PRODUCTION, NULL, 'admin@example.com');

function testMailer() {}

Debugger::$onFatalError[] = function() {
	Assert::match('%a%Fatal error: Call to undefined function missing_funcion() in %a%', file_get_contents(Debugger::$logDirectory . '/error.log'));
	Assert::true(is_file(Debugger::$logDirectory . '/email-sent'));
	die(0);
};
ob_start();


missing_funcion();
