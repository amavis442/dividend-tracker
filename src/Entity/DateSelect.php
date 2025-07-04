<?php

namespace App\Entity;

use DateTimeInterface;

class DateSelect
{

    /**
     * Date
     *
     * @var DateTimeInterface
     */
    private $startdate;

    /**
     * Date
     *
     * @var DateTimeInterface
     */
    private $enddate;


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
     *
     * @param  DateTimeInterface  $startdate  Date
     *
     * @return  self
     */
    public function setStartdate(DateTimeInterface $startdate)
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
     *
     * @param  DateTimeInterface  $enddate  Date
     *
     * @return  self
     */
    public function setEnddate(DateTimeInterface $enddate)
    {
        $this->enddate = $enddate;

        return $this;
    }
}
