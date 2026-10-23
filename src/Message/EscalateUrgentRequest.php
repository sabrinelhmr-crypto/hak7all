<?php

namespace App\Message;

final class EscalateUrgentRequest
{
    public function __construct(public readonly int $requestId)
    {
    }
}
