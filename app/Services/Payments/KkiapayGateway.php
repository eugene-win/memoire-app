<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class KkiapayGateway
{
    public function isConfigured(): bool
    {
        return collect([
            config('services.kkiapay.public_key'),
            config('services.kkiapay.private_key'),
            config('services.kkiapay.secret'),
        ])->every(fn ($value) => is_string($value) && trim($value) !== '');
    }

    public function hasValidWebhookSecret(?string $providedSecret): bool
    {
        $webhookSecret = config('services.kkiapay.webhook_secret');

        return is_string($webhookSecret)
            && $webhookSecret !== ''
            && is_string($providedSecret)
            && $providedSecret !== ''
            && hash_equals($webhookSecret, $providedSecret);
    }

    public function verifyTransaction(string $transactionId): array
    {
        if (! $this->isConfigured()) {
            throw new KkiapayServiceException('Les clés API KKiaPay ne sont pas configurées.');
        }

        $baseUrl = config('services.kkiapay.sandbox')
            ? 'https://api-sandbox.kkiapay.me'
            : 'https://api.kkiapay.me';

        try {
            $response = Http::acceptJson()
                ->timeout(15)
                ->withHeaders([
                    'X-API-KEY' => config('services.kkiapay.public_key'),
                    'X-PRIVATE-KEY' => config('services.kkiapay.private_key'),
                    'X-SECRET-KEY' => config('services.kkiapay.secret'),
                ])
                ->post($baseUrl.'/api/v1/transactions/status', [
                    'transactionId' => $transactionId,
                ]);
        } catch (ConnectionException $exception) {
            throw new KkiapayServiceException('Impossible de joindre le service de vérification KKiaPay.', $exception);
        }

        if (! $response->successful()) {
            throw new KkiapayServiceException('KKiaPay a refusé la vérification de la transaction (HTTP '.$response->status().').');
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new KkiapayServiceException('KKiaPay a retourné une réponse de vérification invalide.');
        }

        return $payload;
    }
}
