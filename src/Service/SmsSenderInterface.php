<?php

namespace App\Service;

interface SmsSenderInterface
{
    public function send(string $phone, string $message): void;
}
