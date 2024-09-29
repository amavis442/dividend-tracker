<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class PortfolioGoal
{
    #[Assert\NotBlank]
    private ?float $goal = null;

    /**
     * Get the value of goal
     */
    public function getGoal(): ?float
    {
        return $this->goal;
    }

    /**
     * Set the value of goal
     *
     * @return  self
     */
    public function setGoal(?float $goal): self
    {
        $this->goal = $goal;

        return $this;
    }
}
