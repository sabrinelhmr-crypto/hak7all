<?php

namespace App\MessageHandler;

use App\Message\SuggestAlternatives;
use App\Repository\ServiceRequestRepository;
use App\Service\RequestNotifier;
use App\Service\SmsSenderInterface;
use App\Util\Phone;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
class EscalateUrgentRequestHandler
{
    public function __construct(
        private readonly ServiceRequestRepository $requests,
        private readonly RequestNotifier $notifier,
        private readonly SmsSenderInterface $smsSender,
        private readonly MessageBusInterface $bus,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(\App\Message\EscalateUrgentRequest $message): void
    {
        $request = $this->requests->find($message->requestId);
        if (null === $request || !$request->isUrgent() || !$request->isPending()) {
            return;
        }

        if (null !== $request->getSeenAt() || null !== $request->getEscalatedAt()) {
            return;
        }

        $request->escalate();
        $this->entityManager->flush();

        $this->notifier->notifyNewRequest($request, true);

        $worker = $request->getWorker();
        $client = $request->getClient();
        if (null !== $worker && null !== $client) {
            $this->smsSender->send(
                $worker->getPhone(),
                sprintf(
                    'Hak 7all — demande URGENTE de %s non lue depuis 3 min : « %s ». Ouvrez l’application.',
                    $client->getFullName(),
                    mb_strimwidth($request->getMessage(), 0, 80, '…')
                )
            );
        }

        $delay = max(0, ($request->getCreatedAt()->modify('+10 minutes')->getTimestamp() - time()) * 1000);
        $this->bus->dispatch(new SuggestAlternatives($request->getId()), [new DelayStamp($delay)]);

        $this->logger->info('Demande urgente {id} escaladée (SMS + relance).', ['id' => $request->getId()]);
    }
}
