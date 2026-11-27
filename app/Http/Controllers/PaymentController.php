<?php

namespace App\Http\Controllers;

use App\Models\Thesis;
use App\Models\ThesisPayment;
use App\Services\ActivityLogger;
use App\Services\Payments\KkiapayGateway;
use App\Services\Payments\KkiapayServiceException;
use App\Services\Payments\PaymentVerificationException;
use App\Services\Payments\ThesisPaymentConfirmationService;
use App\Services\PlatformSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function start(
        Request $request,
        Thesis $thesis,
        KkiapayGateway $gateway,
        PlatformSettings $settings,
        ActivityLogger $activityLogger
    ): View|RedirectResponse {
        $user = $request->user();
        abort_unless($user->hasRole('external_reader') && $user->account_status === 'active', 403);
        abort_unless($thesis->status === 'published' && $thesis->published_at !== null && $thesis->currentVersion, 404);
        $price = $settings->integer('external_access_price_xof', 1000);
        abort_if($price < 1, 503, 'Le tarif d’accès externe n’est pas valide.');

        if ($user->accessGrants()->where('thesis_id', $thesis->id)->where('status', 'active')->exists()) {
            return redirect()->route('thesis-versions.viewer', $thesis->currentVersion);
        }

        if (! $gateway->isConfigured()) {
            return back()->withErrors([
                'payment' => 'Le paiement KKiaPay n’est pas configuré dans cet environnement.',
            ]);
        }

        $payment = DB::transaction(function () use ($user, $thesis, $price, $activityLogger): ?ThesisPayment {
            $lockedThesis = Thesis::query()->lockForUpdate()->findOrFail($thesis->id);
            abort_unless(
                $lockedThesis->status === 'published'
                    && $lockedThesis->published_at !== null
                    && $lockedThesis->currentVersion,
                404
            );

            if ($user->accessGrants()->where('thesis_id', $lockedThesis->id)->where('status', 'active')->exists()) {
                return null;
            }

            $pendingPayment = ThesisPayment::query()
                ->where('user_id', $user->id)
                ->where('thesis_id', $lockedThesis->id)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if ($pendingPayment) {
                return $pendingPayment;
            }

            $payment = ThesisPayment::create([
                'user_id' => $user->id,
                'thesis_id' => $lockedThesis->id,
                'provider' => 'kkiapay',
                'reference' => (string) Str::uuid(),
                'amount' => $price,
                'currency' => 'XOF',
                'status' => 'pending',
            ]);
            $activityLogger->record($user, 'payment.initiated', $payment, [
                'thesis_id' => $lockedThesis->id,
                'amount_xof' => $price,
            ]);

            return $payment;
        });

        if (! $payment) {
            return redirect()->route('thesis-versions.viewer', $thesis->currentVersion);
        }

        return view('payments.checkout', [
            'payment' => $payment,
            'publicKey' => config('services.kkiapay.public_key'),
            'sandbox' => (bool) config('services.kkiapay.sandbox'),
        ]);
    }

    public function confirm(
        Request $request,
        ThesisPayment $payment,
        ThesisPaymentConfirmationService $confirmation
    ): JsonResponse {
        abort_unless($request->user()->account_status === 'active', 403);
        abort_unless($payment->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'transactionId' => ['required', 'string', 'max:128'],
        ]);

        try {
            $status = $confirmation->confirm($payment, $validated['transactionId']);
        } catch (KkiapayServiceException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Le statut du paiement ne peut pas être vérifié pour le moment. Réessayez dans quelques instants.',
            ], 503);
        } catch (PaymentVerificationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'status' => $status,
            'message' => match ($status) {
                'paid' => 'Paiement confirmé; l’accès au mémoire est activé.',
                'failed' => 'Le paiement n’a pas abouti. Vous pouvez réessayer.',
                default => 'Le paiement est en attente de confirmation.',
            },
            'redirect' => $status === 'paid' ? route('dashboard') : null,
        ], $status === 'pending' ? 202 : 200);
    }
}
