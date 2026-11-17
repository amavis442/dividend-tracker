<?php

namespace App\Form\Model;

use App\Entity\Pie;

class PieSelect
{
    /**
     *
     * @var Pie
     */
    private ?Pie $pie = null;

    public function getPie(): ?Pie
    {
        return $this->pie;
    }

    public function setPie(?Pie $pie): self
    {
        $this->pie = $pie ?? new Pie();

        return $this;
    }
}
