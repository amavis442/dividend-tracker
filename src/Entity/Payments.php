<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Payments
 *
 * @ORM\Table(name="payments", indexes={@ORM\Index(name="fk_position_payments", columns={"position_id"})})
 * @ORM\Entity
 */
class Payments
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
     * @var \DateTime|null
     *
     * @ORM\Column(name="exdate", type="date", nullable=true, options={"default"="NULL"})
     */
    private $exdate = 'NULL';

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="paydate", type="date", nullable=true, options={"default"="NULL"})
     */
    private $paydate = 'NULL';

    /**
     * @var string|null
     *
     * @ORM\Column(name="dividend", type="decimal", precision=10, scale=2, nullable=true, options={"default"="0.00"})
     */
    private $dividend = '0.00';

    /**
     * @var \App\Entity\Position
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Position")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="position_id", referencedColumnName="id")
     * })
     */
    private $position;


}
