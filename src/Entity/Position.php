<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Position
 *
 * @ORM\Table(name="position", indexes={@ORM\Index(name="ticker_id", columns={"ticker_id"})})
 * @ORM\Entity
 */
class Position
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="price", type="decimal", precision=10, scale=2, nullable=true, options={"default"="0.00"})
     */
    private $price = '0.00';

    /**
     * @var string|null
     *
     * @ORM\Column(name="amount", type="decimal", precision=10, scale=2, nullable=true, options={"default"="0.00"})
     */
    private $amount = '0.00';

    /**
     * @var \App\Entity\Ticker
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Ticker")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ticker_id", referencedColumnName="id")
     * })
     */
    private $ticker;


}
