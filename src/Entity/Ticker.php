<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ticker
 *
 * @ORM\Table(name="ticker", indexes={@ORM\Index(name="fk_branch_ticker", columns={"branch_id"}), @ORM\Index(name="ticker", columns={"ticker"})})
 * @ORM\Entity
 */
class Ticker
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
     * @var string
     *
     * @ORM\Column(name="ticker", type="string", length=10, nullable=false)
     */
    private $ticker;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fullname", type="string", length=255, nullable=true, options={"default"="NULL"})
     */
    private $fullname = 'NULL';

    /**
     * @var \App\Entity\Branch
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Branch")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="branch_id", referencedColumnName="id")
     * })
     */
    private $branch;


}
