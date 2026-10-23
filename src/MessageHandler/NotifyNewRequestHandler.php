<?php

namespace App\MessageHandler;

use App\Message\NotifyNewRequest;
use App\Repository\ServiceRequestRepository;
use App\Service\RequestNotifier;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NotifyNewRequestHandler
{
    public function __construct(
        private readonly ServiceRequestRepository $requests,
        private readonly RequestNotifier $notifier,
    ) {
    }

    public function __invoke(NotifyNewRequest $message): void
    {
        $request = $this->requests->find($message->requestId);
        if (null === $request) {
            return;
        }

        $this->notifier->notifyNewRequest($request);
    }
}
