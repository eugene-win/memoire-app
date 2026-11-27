<?php

namespace App\Services\Payments;

use App\Models\ThesisAccessGrant;
use App\Models\ThesisPayment;
use App\Notifications\ThesisWorkflowNotification;
use Illuminate\Support\Facades\DB;

class ThesisPaymentConfirmationService
{
    public function __construct(private readonly KkiapayGateway $gateway) {}

    public function confirm(ThesisPayment $payment, string $transactionId): string
    {
        $providerResult = $this->gateway->verifyTransaction($transactionId);
        $status = strtoupper((string) ($providerResult['status'] ?? ''));

        if (($providerResult['transactionId'] ?? null) !== $transactionId) {
            throw new PaymentVerificationException('La référence de transaction retournée par KKiaPay ne correspond pas.');
        }

        if (($providerResult['partnerId'] ?? null) !== $payment->reference) {
            throw new PaymentVerificationException('La transaction vérifiée ne correspond pas au paiement initié.');
        }

        if (! is_numeric($providerResult['amount'] ?? null) || (float) $providerResult['amount'] !== (float) $payment->amount) {
            throw new PaymentVerificationException('Le montant confirmé par KKiaPay ne correspond pas au montant attendu.');
        }

        if (! in_array($status, ['SUCCESS', 'FAILED', 'PENDING'], true)) {
            throw new PaymentVerificationException('Le statut retourné par KKiaPay est inconnu.');
        }

        $safeProviderPayload = [
            'status' => $status,
            'transactionId' => $transactionId,
            'partnerId' => $payment->reference,
            'source' => $providerResult['source'] ?? null,
            'performed_at' => $providerResult['performed_at'] ?? $providerResult['performedAt'] ?? null,
        ];

        if ($status === 'SUCCESS') {
            return $this->grantAccess($payment, $transactionId, $safeProviderPayload);
        }

        return DB::transaction(function () use ($payment, $transactionId, $status, $safeProviderPayload): string {
            $lockedPayment = ThesisPayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status === 'paid') {
                if ($lockedPayment->provider_transaction_id !== $transactionId) {
                    throw new PaymentVerificationException('Un autre paiement a déjà été confirmé pour cette tentative.');
                }

                return 'paid';
            }

            if ($lockedPayment->status === 'failed') {
                return 'failed';
            }

            $lockedPayment->update([
                'provider_transaction_id' => $transactionId,
                'status' => $status === 'FAILED' ? 'failed' : 'pending',
                'provider_payload' => $safeProviderPayload,
            ]);

            if ($status === 'FAILED') {
                $lockedPayment->user->notify(new ThesisWorkflowNotification(
                    'payment_failed',
                    'Paiement non abouti',
                    'Le paiement de l’accès au mémoire « '.$lockedPayment->thesis->title.' » n’a pas été confirmé. Vous pourrez réessayer.',
                    $lockedPayment->thesis_id,
                    $lockedPayment->thesis->currentVersion->id,
                ));
            }

            return $status === 'FAILED' ? 'failed' : 'pending';
        });
    }

    private function grantAccess(ThesisPayment $payment, string $transactionId, array $providerPayload): string
    {
        return DB::transaction(function () use ($payment, $transactionId, $providerPayload): string {
            $lockedPayment = ThesisPayment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status === 'paid') {
                if ($lockedPayment->provider_transaction_id !== $transactionId) {
                    throw new PaymentVerificationException('Un autre paiement a déjà été confirmé pour cette tentative.');
                }

                return 'paid';
            }

            if ($lockedPayment->status !== 'pending') {
                throw new PaymentVerificationException('Cette tentative de paiement n’est plus en attente.');
            }

            $thesis = $lockedPayment->thesis;
            $version = $thesis->currentVersion;
            if (! $version) {
                throw new PaymentVerificationException('Aucune version du mémoire ne peut être associée à cet accès.');
            }

            $existingGrant = ThesisAccessGrant::query()
                ->where('user_id', $lockedPayment->user_id)
                ->where('thesis_id', $lockedPayment->thesis_id)
                ->lockForUpdate()
                ->first();

            if ($existingGrant && $existingGrant->status === 'active' && $existingGrant->thesis_payment_id !== $lockedPayment->id) {
                throw new PaymentVerificationException('Un accès existe déjà pour ce compte et ce mémoire; la transaction nécessite une vérification manuelle.');
            }

            $lockedPayment->update([
                'provider_transaction_id' => $transactionId,
                'status' => 'paid',
                'provider_payload' => $providerPayload,
                'confirmed_at' => now(),
            ]);

            ThesisAccessGrant::query()->updateOrCreate(
                [
                    'user_id' => $lockedPayment->user_id,
                    'thesis_id' => $lockedPayment->thesis_id,
                ],
                [
                    'thesis_payment_id' => $lockedPayment->id,
                    'origin' => 'payment',
                    'status' => 'active',
                    'granted_at' => now(),
                ]
            );

            $user = $lockedPayment->user;
            $user->notify(new ThesisWorkflowNotification(
                'payment_confirmed',
                'Paiement confirmé',
                'Le paiement de 1 000 F CFA pour « '.$thesis->title.' » a été confirmé.',
                $thesis->id,
                $version->id,
            ));
            $user->notify(new ThesisWorkflowNotification(
                'access_granted',
                'Accès accordé',
                'Vous pouvez maintenant consulter intégralement « '.$thesis->title.' » dans votre espace lecteur.',
                $thesis->id,
                $version->id,
            ));

            return 'paid';
        });
    }
}
