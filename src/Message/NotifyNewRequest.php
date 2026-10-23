<?php

namespace App\Message;

final class NotifyNewRequest
{
    public function __construct(public readonly int $requestId)
    {
    }
}
