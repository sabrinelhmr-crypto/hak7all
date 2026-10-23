<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\ServiceRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RequestNotifier
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly WebPushSender $webPush,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function notifyNewRequest(ServiceRequest $request, bool $escalation = false): void
    {
        $worker = $request->getWorker();
        $client = $request->getClient();
        if (null === $worker || null === $client) {
            return;
        }

        $urgent = $request->isUrgent();
        $title = $escalation
            ? 'Relance — demande urgente'
            : ($urgent ? 'Demande urgente' : 'Nouvelle demande');

        $payload = [
            'type' => $escalation ? 'urgent_escalation' : 'new_request',
            'requestId' => $request->getId(),
            'title' => $title,
            'body' => sprintf('%s : %s', $client->getFullName(), mb_strimwidth($request->getMessage(), 0, 120, '…')),
            'clientName' => $client->getFullName(),
            'message' => $request->getMessage(),
            'isUrgent' => $urgent,
            'createdAt' => $request->getCreatedAt()->format(\DATE_ATOM),
            'url' => $this->urlGenerator->generate('app_worker_requests', [], UrlGeneratorInterface::ABSOLUTE_PATH).'#demande-'.$request->getId(),
        ];

        $this->publish($this->userTopic($worker), $payload);

        if (!$escalation) {
            $this->recordNotification($worker, $urgent ? 'request_urgent' : 'request_new', $payload);
        }

        $this->webPush->sendToUser($worker, $payload, $urgent);
    }

    public function notifyStatus(ServiceRequest $request): void
    {
        $client = $request->getClient();
        $worker = $request->getWorker();
        if (null === $client || null === $worker) {
            return;
        }

        $accepted = $request->isAccepted();
        $payload = [
            'type' => 'request_status',
            'requestId' => $request->getId(),
            'status' => $request->getStatus(),
            'statusLabel' => $request->getStatusLabel(),
            'title' => $accepted ? 'Demande acceptée' : 'Demande refusée',
            'body' => $accepted
                ? sprintf('%s a accepté votre demande. Vous pouvez le contacter au %s.', $worker->getFullName(), \App\Util\Phone::format($worker->getPhone()))
                : sprintf('%s a refusé votre demande.', $worker->getFullName()),
            'workerName' => $worker->getFullName(),
            'workerPhone' => $accepted ? \App\Util\Phone::format($worker->getPhone()) : null,
            'isUrgent' => $request->isUrgent(),
            'url' => $this->urlGenerator->generate('app_client_requests', [], UrlGeneratorInterface::ABSOLUTE_PATH).'#demande-'.$request->getId(),
        ];

        $this->publish($this->userTopic($client), $payload);
        $this->recordNotification($client, 'request_'.$request->getStatus(), $payload);
        $this->webPush->sendToUser($client, $payload, false);
    }

    public function notifyNoResponse(ServiceRequest $request, int $alternativesCount): void
    {
        $client = $request->getClient();
        if (null === $client) {
            return;
        }

        $payload = [
            'type' => 'request_no_response',
            'requestId' => $request->getId(),
            'title' => 'Toujours pas de réponse',
            'body' => sprintf(
                "L'artisan n'a pas répondu depuis 10 minutes. %d autre(s) artisan(s) disponible(s) peuvent vous aider.",
                $alternativesCount
            ),
            'alternativesCount' => $alternativesCount,
            'isUrgent' => true,
            'url' => $this->urlGenerator->generate('app_client_requests', [], UrlGeneratorInterface::ABSOLUTE_PATH).'#demande-'.$request->getId(),
        ];

        $this->publish($this->userTopic($client), $payload);
        $this->recordNotification($client, 'request_no_response', $payload);
        $this->webPush->sendToUser($client, $payload, true);
    }

    public function publish(string $topic, array $payload): void
    {
        try {
            $this->hub->publish(new Update($topic, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), true));
        } catch (\Throwable $e) {
            $this->logger->warning('Mercure indisponible : {error}', ['error' => $e->getMessage()]);
        }
    }

    public function userTopic(User $user): string
    {
        return sprintf('user/%d/requests', $user->getId());
    }

    private function recordNotification(User $user, string $type, array $payload): void
    {
        $notification = (new Notification())
            ->setUser($user)
            ->setType($type)
            ->setPayload($payload);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }
}
