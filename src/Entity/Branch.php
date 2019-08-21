<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Branch
 *
 * @ORM\Table(name="branch", indexes={@ORM\Index(name="label", columns={"label"})})
 * @ORM\Entity
 */
class Branch
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
     * @var int|null
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=true)
     */
    private $parentId = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="label", type="string", length=255, nullable=true, options={"default"="NULL"})
     */
    private $label = 'NULL';


}
