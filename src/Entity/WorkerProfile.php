<?php

namespace App\Entity;

use App\Repository\WorkerProfileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkerProfileRepository::class)]
#[ORM\Table(name: 'worker_profile')]
#[ORM\Index(name: 'idx_worker_trade_available', columns: ['trade_id', 'is_available'])]
class WorkerProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'workerProfile', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Trade $trade = null;

    #[ORM\Column(nullable: true)]
    private ?int $rateTnd = null;

    #[ORM\Column]
    private bool $isAvailable = false;

    #[ORM\Column]
    private bool $phonePublic = false;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTrade(): ?Trade
    {
        return $this->trade;
    }

    public function setTrade(?Trade $trade): self
    {
        $this->trade = $trade;

        return $this;
    }

    public function getRateTnd(): ?int
    {
        return $this->rateTnd;
    }

    public function setRateTnd(?int $rateTnd): self
    {
        $this->rateTnd = $rateTnd;

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): self
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    public function isPhonePublic(): bool
    {
        return $this->phonePublic;
    }

    public function setPhonePublic(bool $phonePublic): self
    {
        $this->phonePublic = $phonePublic;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
