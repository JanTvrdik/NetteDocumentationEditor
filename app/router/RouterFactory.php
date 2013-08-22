<?php

namespace App;

use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\SimpleRouter;


/**
 * Router factory.
 */
class RouterFactory extends Nette\Object
{

	/**
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new Route('<action>[/<branch>/<path .+>]', [
			'presenter' => 'Editor',
			'action' => [
				Route::VALUE => 'default',
				Route::FILTER_TABLE => ['edit' => 'default'],
			]
		]);

		return $router;
	}

}
