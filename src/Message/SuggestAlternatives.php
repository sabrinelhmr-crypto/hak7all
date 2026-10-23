<?php

namespace App\Message;

final class SuggestAlternatives
{
    public function __construct(public readonly int $requestId)
    {
    }
}
