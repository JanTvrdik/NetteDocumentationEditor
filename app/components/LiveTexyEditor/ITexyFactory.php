<?php
namespace App;

use Texy;


interface ITexyFactory
{
	/**
	 * @return Texy
	 */
	public function create();
}
