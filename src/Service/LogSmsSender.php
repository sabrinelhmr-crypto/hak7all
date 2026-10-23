<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * Implémentation de secours : écrit le SMS dans les logs.
 * Brancher plus tard un fournisseur tunisien (Ooredoo, Orange, Tunisie Telecom, agrégateur).
 */
class LogSmsSender implements SmsSenderInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function send(string $phone, string $message): void
    {
        $this->logger->info('[SMS] vers +216 {phone} : {message}', [
            'phone' => $phone,
            'message' => $message,
        ]);
    }
}
