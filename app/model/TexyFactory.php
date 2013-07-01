<?php
namespace App;

use Nette;
use Texy;


class TexyFactory extends Nette\Object implements ITexyFactory
{

	public function create()
	{
		return new Texy(); // TODO: use real configuration
	}

}
