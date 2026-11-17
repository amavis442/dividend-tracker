<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeInterface;

class DateSelect
{
    /**
     * Date
     */
    private ?\DateTimeInterface $startdate = null;

    /**
     * Date
     */
    private ?\DateTimeInterface $enddate = null;


    /**
     * Get date
     *
     * @return  DateTimeInterface
     */
    public function getStartdate()
    {
        return $this->startdate;
    }

    /**
     * Set date
     */
    public function setStartdate(DateTimeInterface $startdate): static
    {
        $this->startdate = $startdate;

        return $this;
    }

    /**
     * Get date
     *
     * @return  DateTimeInterface
     */
    public function getEnddate()
    {
        return $this->enddate;
    }

    /**
     * Set date
     */
    public function setEnddate(DateTimeInterface $enddate): static
    {
        $this->enddate = $enddate;

        return $this;
    }
}
