@extends('layouts.library')

@section('title', 'Paiement sécurisé · ENEAM')

@section('content')
    <section class="dashboard-page">
        <div class="container">
            <div class="dashboard-welcome">
                <div>
                    <span class="section-kicker">Paiement sécurisé</span>
                    <h1>Accéder au mémoire</h1>
                    <p>{{ $payment->thesis->title }}</p>
                </div>
                @if ($sandbox)
                    <span class="account-badge">Mode test KKiaPay</span>
                @endif
            </div>

            <article class="dashboard-card">
                <h2>Montant à régler : 1 000 F CFA</h2>
                <p>Votre accès ne sera activé qu’après vérification du paiement par le serveur.</p>
                <button class="button" id="kkiapay-pay" type="button">Payer avec KKiaPay</button>
                <button class="button button-outline" id="verify-payment" type="button" hidden>Vérifier à nouveau le paiement</button>
                <p class="form-note" id="payment-status" role="status" aria-live="polite"></p>
                <a class="button button-outline" href="{{ route('home') }}#catalogue">Retour au catalogue</a>
            </article>
        </div>
    </section>
@endsection

@section('scripts')
    <script src="https://cdn.kkiapay.me/k.js"></script>
    <script>
        (() => {
            const button = document.querySelector('#kkiapay-pay');
            const verifyButton = document.querySelector('#verify-payment');
            const status = document.querySelector('#payment-status');
            const dashboardUrl = @json(route('dashboard'));
            const confirmUrl = @json(route('payments.confirm', $payment));
            let pendingTransactionId = null;

            const confirmPayment = async (transactionId) => {
                pendingTransactionId = transactionId;
                status.textContent = 'Vérification du paiement en cours…';
                button.disabled = true;
                verifyButton.hidden = true;
                verifyButton.disabled = true;

                try {
                    const response = await fetch(confirmUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @json(csrf_token()),
                        },
                        body: JSON.stringify({ transactionId }),
                    });
                    const result = await response.json();

                    if (!response.ok && response.status !== 202) {
                        throw new Error(result.message || 'La vérification du paiement a échoué.');
                    }

                    status.textContent = result.message;
                    if (result.status === 'paid') {
                        window.location.assign(result.redirect || dashboardUrl);
                    } else if (result.status === 'failed') {
                        window.setTimeout(() => window.location.assign(dashboardUrl), 1800);
                    } else {
                        verifyButton.hidden = false;
                        verifyButton.disabled = false;
                    }
                } catch (error) {
                    status.textContent = error.message || 'Le paiement sera vérifié à nouveau lorsque le service sera disponible.';
                    verifyButton.hidden = false;
                    verifyButton.disabled = false;
                }
            };

            verifyButton.addEventListener('click', () => {
                if (pendingTransactionId) confirmPayment(pendingTransactionId);
            });

            button.addEventListener('click', () => {
                if (typeof window.openKkiapayWidget !== 'function') {
                    status.textContent = 'Le service de paiement est momentanément indisponible. Réessayez plus tard.';
                    return;
                }

                button.disabled = true;
                status.textContent = 'Ouverture de la fenêtre de paiement…';
                window.openKkiapayWidget({
                    amount: @json($payment->amount),
                    key: @json($publicKey),
                    position: 'center',
                    sandbox: @json($sandbox),
                    partnerId: @json($payment->reference),
                    data: @json('memoire-eneam:'.$payment->reference),
                });
            });

            if (typeof window.addSuccessListener === 'function') {
                window.addSuccessListener((result) => {
                    if (!result?.transactionId) {
                        status.textContent = 'KKiaPay n’a pas fourni de référence de transaction. Contactez le support avant de recommencer.';
                        return;
                    }

                    confirmPayment(result.transactionId);
                });
            }

            if (typeof window.addFailedListener === 'function') {
                window.addFailedListener(() => {
                    status.textContent = 'Le paiement n’a pas abouti. Vous pouvez réessayer.';
                    button.disabled = false;
                });
            }
        })();
    </script>
@endsection
