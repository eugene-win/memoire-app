<?php

namespace App\Services\Payments;

interface PaymentGateway
{
    public function providerName(): string;

    public function verifyTransaction(string $transactionId): array;
}
