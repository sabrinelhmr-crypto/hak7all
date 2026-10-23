<?php

namespace App\MessageHandler;

use App\Message\SuggestAlternatives;
use App\Repository\ServiceRequestRepository;
use App\Repository\WorkerProfileRepository;
use App\Service\RequestNotifier;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SuggestAlternativesHandler
{
    public function __construct(
        private readonly ServiceRequestRepository $requests,
        private readonly WorkerProfileRepository $profiles,
        private readonly RequestNotifier $notifier,
    ) {
    }

    public function __invoke(SuggestAlternatives $message): void
    {
        $request = $this->requests->find($message->requestId);
        if (null === $request || !$request->isPending() || null !== $request->getSeenAt()) {
            return;
        }

        $worker = $request->getWorker();
        $client = $request->getClient();
        if (null === $worker || null === $client) {
            return;
        }

        $profile = $worker->getWorkerProfile();
        $trade = $profile?->getTrade();
        $city = $worker->getCity();
        if (null === $trade || null === $city) {
            return;
        }

        $alternatives = $this->profiles->findAlternatives($trade, $city);
        if ([] === $alternatives) {
            return;
        }

        $this->notifier->notifyNoResponse($request, count($alternatives));
    }
}
