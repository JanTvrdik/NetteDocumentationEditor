<?php
namespace App;

require __DIR__ . '/../vendor/autoload.php';

class_alias('Nette\DI\CompilerExtension', 'Nette\Config\CompilerExtension');


$configurator = new \Nette\Configurator();
$configurator->enableDebugger(__DIR__ . '/../log');
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()->addDirectory(__DIR__)->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');
$container = $configurator->createContainer();

return $container;
