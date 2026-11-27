<?php

namespace App\Http\Controllers;

use App\Models\PaymentWebhookReceipt;
use App\Models\ThesisPayment;
use App\Services\Payments\KkiapayGateway;
use App\Services\Payments\KkiapayServiceException;
use App\Services\Payments\PaymentVerificationException;
use App\Services\Payments\ThesisPaymentConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        KkiapayGateway $gateway,
        ThesisPaymentConfirmationService $confirmation
    ): JsonResponse {
        abort_unless($gateway->hasValidWebhookSecret($request->header('x-kkiapay-secret')), 403);

        $validated = $request->validate([
            'transactionId' => ['required', 'string', 'max:128'],
            'partnerId' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'min:0'],
            'isPaymentSucces' => ['required', 'boolean'],
            'event' => ['required', Rule::in(['transaction.success', 'transaction.failed'])],
        ]);

        $payment = ThesisPayment::query()
            ->where('provider', 'kkiapay')
            ->where('reference', $validated['partnerId'])
            ->firstOrFail();

        abort_unless((float) $validated['amount'] === (float) $payment->amount, 422);
        abort_unless(
            $validated['isPaymentSucces'] === ($validated['event'] === 'transaction.success'),
            422,
            'Le statut de l’événement ne correspond pas à son indicateur de succès.'
        );

        $eventId = $validated['transactionId'].':'.$validated['event'];
        $payloadHash = hash('sha256', json_encode($validated, JSON_THROW_ON_ERROR));
        $receipt = DB::transaction(function () use ($payment, $eventId, $payloadHash): PaymentWebhookReceipt {
            $receipt = PaymentWebhookReceipt::query()->firstOrCreate(
                [
                    'provider' => $payment->provider,
                    'provider_event_id' => $eventId,
                ],
                [
                    'thesis_payment_id' => $payment->id,
                    'payload_hash' => $payloadHash,
                    'processing_status' => 'received',
                    'received_at' => now(),
                ]
            );
            abort_unless(hash_equals((string) $receipt->payload_hash, $payloadHash), 409, 'L’événement de paiement a déjà été reçu avec un contenu différent.');

            return $receipt;
        });

        if ($receipt->processing_status === 'processed') {
            return response()->json(['received' => true, 'status' => $payment->fresh()->status, 'duplicate' => true]);
        }

        try {
            $status = $confirmation->confirm($payment, $validated['transactionId']);
        } catch (KkiapayServiceException $exception) {
            report($exception);
            $receipt->update([
                'processing_status' => 'failed',
                'failure_reason' => 'Vérification KKiaPay indisponible.',
            ]);

            return response()->json(['message' => 'Vérification KKiaPay temporairement indisponible.'], 503);
        } catch (PaymentVerificationException $exception) {
            $receipt->update([
                'processing_status' => 'failed',
                'failure_reason' => $exception->getMessage(),
            ]);

            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if ($status === 'pending') {
            $receipt->update(['processing_status' => 'pending']);

            return response()->json(['message' => 'Le statut de la transaction est encore en attente.'], 503);
        }
        $receipt->update([
            'processing_status' => 'processed',
            'processed_at' => now(),
            'failure_reason' => null,
        ]);

        return response()->json(['received' => true, 'status' => $status]);
    }
}
