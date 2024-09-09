<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['currency:read']],
    denormalizationContext: ['groups' => ['currency:write']],
    security: 'is_granted("ROLE_USER")',
    operations: [
        new Get(),
        new GetCollection()
    ]
)]
#[ORM\Entity(repositoryClass: 'App\Repository\CurrencyRepository')]
class Currency
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    private ?int $id = null;

    #[Groups(['currency:read', 'currency:write', 'ticker:read:item', 'position:read:item', 'transaction:read', 'calendar:read'])]
    #[ORM\Column(type: 'string', length: 10)]
    private $symbol;

    #[Groups(['currency:read', 'currency:write', 'ticker:read:item', 'position:read:item', 'transaction:read', 'calendar:read'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $description;

    #[Groups(['currency:read', 'currency:write', 'ticker:read:item', 'position:read:item', 'transaction:read', 'calendar:read'])]
    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private $sign;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSign(): ?string
    {
        return $this->sign;
    }

    public function setSign(string $sign): self
    {
        $this->sign = $sign;

        return $this;
    }
}
