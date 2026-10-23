<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Log\LoggerInterface;

class WebPushSender
{
    public function __construct(
        private readonly PushSubscriptionRepository $subscriptions,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly string $vapidPublicKey,
        private readonly string $vapidPrivateKey,
        private readonly string $vapidSubject,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function sendToUser(User $user, array $payload, bool $urgent = false): void
    {
        if ('' === trim($this->vapidPrivateKey) || '' === trim($this->vapidPublicKey)) {
            $this->logger->debug('Web Push ignoré : clés VAPID absentes.');

            return;
        }

        $subscriptions = $this->subscriptions->findForUser($user);
        if ([] === $subscriptions) {
            return;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => $this->vapidSubject,
                    'publicKey' => $this->vapidPublicKey,
                    'privateKey' => $this->vapidPrivateKey,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Web Push : configuration VAPID invalide : {error}', ['error' => $e->getMessage()]);

            return;
        }

        $options = [
            'TTL' => $urgent ? 3600 : 900,
            'urgency' => $urgent ? 'high' : 'normal',
        ];

        foreach ($subscriptions as $subscription) {
            try {
                $webPush->queueNotification(
                    Subscription::create($subscription->toWebPushArray()),
                    json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    $options
                );
            } catch (\Throwable $e) {
                $this->logger->warning('Web Push : abonnement ignoré : {error}', ['error' => $e->getMessage()]);
            }
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }

            $this->logger->info('Web Push échoué : {reason}', [
                'reason' => $report->getReason(),
                'endpoint' => $report->getEndpoint(),
            ]);

            if ($report->isSubscriptionExpired()) {
                $expired = $this->subscriptions->findOneByEndpoint($report->getEndpoint());
                if (null !== $expired) {
                    $this->entityManager->remove($expired);
                }
            }
        }

        $this->entityManager->flush();
    }
}
