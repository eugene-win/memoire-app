<?php

namespace App\Http\Controllers;

use App\Models\ThesisPayment;
use App\Services\Payments\KkiapayGateway;
use App\Services\Payments\KkiapayServiceException;
use App\Services\Payments\PaymentVerificationException;
use App\Services\Payments\ThesisPaymentConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        try {
            $status = $confirmation->confirm($payment, $validated['transactionId']);
        } catch (KkiapayServiceException $exception) {
            report($exception);

            return response()->json(['message' => 'Vérification KKiaPay temporairement indisponible.'], 503);
        } catch (PaymentVerificationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        if ($status === 'pending') {
            return response()->json(['message' => 'Le statut de la transaction est encore en attente.'], 503);
        }

        return response()->json(['received' => true, 'status' => $status]);
    }
}
