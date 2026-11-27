<section class="dashboard-page">
    <div class="container">
        <div class="dashboard-welcome">
            <div>
                <span class="section-kicker">Espace lecteur</span>
                <h1>Bonjour, {{ auth()->user()->name }}</h1>
                <p>Retrouvez vos mémoires achetés, transactions et consultations.</p>
            </div>
            <span class="account-badge">Lecteur externe</span>
        </div>

        @include('components.library.dashboard.notifications')

        <div class="dashboard-grid">
            <article class="dashboard-card dashboard-reading">
                <div class="dashboard-card-heading">
                    <span class="benefit-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 5.5c3-.8 5.7-.3 8 1.5v13c-2.3-1.8-5-2.3-8-1.5z"/><path d="M20 5.5c-3-.8-5.7-.3-8 1.5v13c2.3-1.8 5-2.3 8-1.5z"/>
                        </svg>
                    </span>
                    <div>
                        <h2>Mes mémoires achetés</h2>
                        <p>Vos droits d’accès intégraux confirmés.</p>
                    </div>
                </div>
                @forelse ($accessGrants as $grant)
                    <div class="dashboard-empty">
                        <strong>{{ $grant->thesis->title }}</strong>
                        <p>{{ $grant->thesis->author_name }} · Accès accordé le {{ $grant->granted_at->format('d/m/Y') }}</p>
                        @if ($grant->thesis->currentVersion)
                            <a class="button" href="{{ route('thesis-versions.viewer', $grant->thesis->currentVersion) }}">Lire le mémoire</a>
                        @endif
                    </div>
                @empty
                    <div class="dashboard-empty">
                        <strong>Vous n’avez pas encore acheté de mémoire</strong>
                        <p>Les mémoires acquis apparaîtront ici après confirmation du paiement.</p>
                        <a class="button" href="{{ url('/#catalogue') }}">Parcourir le catalogue</a>
                    </div>
                @endforelse
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card-heading">
                    <span class="benefit-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/><path d="m9 15 2 2 4-4"/>
                        </svg>
                    </span>
                    <div>
                        <h2>Mes transactions</h2>
                        <p>Suivi des paiements par mémoire.</p>
                    </div>
                </div>
                @forelse ($payments as $payment)
                    <div class="dashboard-empty dashboard-empty-compact">
                        <strong>{{ $payment->thesis->title }}</strong>
                        <p>1 000 F CFA ·
                            @switch($payment->status)
                                @case('paid') Paiement confirmé @break
                                @case('failed') Paiement échoué @break
                                @default En attente de confirmation
                            @endswitch
                            · {{ $payment->created_at->format('d/m/Y') }}
                        </p>
                    </div>
                @empty
                    <div class="dashboard-empty dashboard-empty-compact">
                        <strong>Aucune transaction</strong>
                        <p>Le tarif d’accès externe est de 1 000 F CFA par mémoire.</p>
                    </div>
                @endforelse
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card-heading">
                    <span class="benefit-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                        </svg>
                    </span>
                    <div>
                        <h2>Historique de consultation</h2>
                        <p>Reprenez vos lectures précédentes.</p>
                    </div>
                </div>
                @forelse ($consultations as $consultation)
                    <div class="dashboard-empty dashboard-empty-compact">
                        <strong>{{ $consultation->thesis->title }}</strong>
                        <p>Version {{ $consultation->version->version_number }} · {{ $consultation->consulted_at->format('d/m/Y H:i') }}</p>
                        <a class="text-button" href="{{ route('thesis-versions.viewer', $consultation->version) }}">Reprendre la lecture</a>
                    </div>
                @empty
                    <div class="dashboard-empty dashboard-empty-compact">
                        <strong>Aucune consultation enregistrée</strong>
                        <p>Votre historique apparaîtra ici.</p>
                    </div>
                @endforelse
            </article>

            <article class="dashboard-card dashboard-notice">
                <span class="notice-symbol" aria-hidden="true">i</span>
                <div>
                    <h2>Accès intégral sécurisé</h2>
                    <p>La consultation complète est disponible dans la plateforme après confirmation du paiement. Les téléchargements locaux ne sont pas proposés.</p>
                </div>
            </article>
        </div>
    </div>
</section>
