<?php

namespace App\Entity;

use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PushSubscriptionRepository::class)]
#[ORM\Table(name: 'push_subscription')]
class PushSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 500, unique: true)]
    private string $endpoint = '';

    /** @var array{p256dh: string, auth: string} */
    #[ORM\Column(type: 'json')]
    private array $keys = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /** @return array{p256dh: string, auth: string} */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /** @param array{p256dh: string, auth: string} $keys */
    public function setKeys(array $keys): self
    {
        $this->keys = $keys;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return array{endpoint: string, keys: array{p256dh: string, auth: string}} */
    public function toWebPushArray(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => $this->keys,
        ];
    }
}
