<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
#[ORM\Table("users")]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLES = ['user', 'admin', 'superadmin'];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', unique: true, nullable: true)]
    private ?string $apiToken = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\OneToMany(targetEntity: 'App\Entity\Position', mappedBy: 'user')]
    private ?Collection $positions = null;

    #[ORM\OneToMany(targetEntity: 'App\Entity\Payment', mappedBy: 'user')]
    private ?Collection $payments = null;

    #[ORM\OneToMany(targetEntity: 'App\Entity\Journal', mappedBy: 'user')]
    private ?Collection $journals = null;

    #[ORM\OneToMany(targetEntity: DividendTracker::class, mappedBy: 'user', orphanRemoval: true)]
    private ?Collection $dividendTrackers = null;

    #[ORM\OneToMany(targetEntity: Pie::class, mappedBy: 'user', orphanRemoval: true)]
    private ?Collection $pies = null;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
        $this->positions = new ArrayCollection();
        $this->journals = new ArrayCollection();
        $this->dividendTrackers = new ArrayCollection();
        $this->pies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }


    /**
     * @deprecated since Symfony 5.3
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection|Position[]
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    /**
     * @return Collection|Payment[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPosition(Position $position): self
    {
        if (!$this->positions->contains($position)) {
            $this->positions[] = $position;
            $position->setUser($this);
        }

        return $this;
    }

    public function removePosition(Position $position): self
    {
        if ($this->positions->contains($position)) {
            $this->positions->removeElement($position);
        }

        return $this;
    }

    public function addPayment(Payment $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setUser($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        if ($this->payments->contains($payment)) {
            $this->payments->removeElement($payment);
        }

        return $this;
    }

    /**
     * @return Collection|Journal[]
     */
    public function getJournals(): ?Collection
    {
        return $this->journals;
    }

    public function addJournal(Journal $journal): self
    {
        if (!$this->journals->contains($journal)) {
            $this->journals[] = $journal;
            $journal->setUser($this);
        }

        return $this;
    }

    public function removeJournal(Journal $journal): self
    {
        if ($this->journals->contains($journal)) {
            $this->journals->removeElement($journal);
        }

        return $this;
    }

    /**
     * Get the value of apiToken
     */
    public function getApiToken()
    {
        return $this->apiToken;
    }

    /**
     * Set the value of apiToken
     *
     * @return  self
     */
    public function setApiToken($apiToken)
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    /**
     * @return Collection|DividendTracker[]
     */
    public function getDividendTrackers(): ?Collection
    {
        return $this->dividendTrackers;
    }

    public function addDividendTracker(DividendTracker $dividendTracker): self
    {
        if (!$this->dividendTrackers->contains($dividendTracker)) {
            $this->dividendTrackers[] = $dividendTracker;
            $dividendTracker->setUser($this);
        }

        return $this;
    }

    public function removeDividendTracker(DividendTracker $dividendTracker): self
    {
        if ($this->dividendTrackers->removeElement($dividendTracker)) {
            // set the owning side to null (unless already changed)
            if ($dividendTracker->getUser() === $this) {
                $dividendTracker->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Pie[]
     */
    public function getPies(): ?Collection
    {
        return $this->pies;
    }

    public function addPy(Pie $py): self
    {
        if (!$this->pies->contains($py)) {
            $this->pies[] = $py;
            $py->setUser($this);
        }

        return $this;
    }

    public function removePy(Pie $py): self
    {
        if ($this->pies->removeElement($py)) {
            // set the owning side to null (unless already changed)
            if ($py->getUser() === $this) {
                $py->setUser(null);
            }
        }

        return $this;
    }
}
