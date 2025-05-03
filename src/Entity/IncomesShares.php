<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class IncomesShares
{
	protected Collection $shares;

	public function __construct()
	{
		$this->shares = new ArrayCollection();
	}

	public function getShares(): Collection
	{
		return $this->shares;
	}
}
