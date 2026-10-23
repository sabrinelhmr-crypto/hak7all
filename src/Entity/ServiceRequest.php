<?php

namespace App\Entity;

use App\Repository\ServiceRequestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRequestRepository::class)]
#[ORM\Table(name: 'service_request')]
#[ORM\Index(name: 'idx_request_worker_status', columns: ['worker_id', 'status'])]
#[ORM\Index(name: 'idx_request_client_created', columns: ['client_id', 'created_at'])]
class ServiceRequest
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED = 'expired';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'En attente',
        self::STATUS_ACCEPTED => 'Acceptée',
        self::STATUS_DECLINED => 'Refusée',
        self::STATUS_EXPIRED => 'Expirée',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $client = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $worker = null;

    #[ORM\Column(type: 'text')]
    private string $message = '';

    #[ORM\Column]
    private bool $isUrgent = false;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $seenAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $answeredAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $escalatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(User $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getWorker(): ?User
    {
        return $this->worker;
    }

    public function setWorker(User $worker): self
    {
        $this->worker = $worker;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = trim($message);

        return $this;
    }

    public function isUrgent(): bool
    {
        return $this->isUrgent;
    }

    public function setIsUrgent(bool $isUrgent): self
    {
        $this->isUrgent = $isUrgent;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!array_key_exists($status, self::STATUS_LABELS)) {
            throw new \InvalidArgumentException('Statut inconnu.');
        }

        $this->status = $status;

        return $this;
    }

    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSeenAt(): ?\DateTimeImmutable
    {
        return $this->seenAt;
    }

    public function markSeen(): self
    {
        $this->seenAt ??= new \DateTimeImmutable();

        return $this;
    }

    public function getAnsweredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function accept(): self
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->answeredAt = new \DateTimeImmutable();

        return $this;
    }

    public function decline(): self
    {
        $this->status = self::STATUS_DECLINED;
        $this->answeredAt = new \DateTimeImmutable();

        return $this;
    }

    public function expire(): self
    {
        $this->status = self::STATUS_EXPIRED;

        return $this;
    }

    public function getEscalatedAt(): ?\DateTimeImmutable
    {
        return $this->escalatedAt;
    }

    public function escalate(): self
    {
        $this->escalatedAt ??= new \DateTimeImmutable();

        return $this;
    }

    public function isPending(): bool
    {
        return self::STATUS_PENDING === $this->status;
    }

    public function isAccepted(): bool
    {
        return self::STATUS_ACCEPTED === $this->status;
    }

    public function isOlderThan(int $minutes): bool
    {
        return $this->createdAt <= (new \DateTimeImmutable())->modify(sprintf('-%d minutes', $minutes));
    }

    public function isAwaitingAnswerFor(int $minutes): bool
    {
        return $this->isPending() && $this->isOlderThan($minutes);
    }
}
