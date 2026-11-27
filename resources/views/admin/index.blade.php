@extends('layouts.library')

@section('title', 'Administration · Bibliothèque ENEAM')

@section('content')
    <section class="admin-page">
        <div class="container">
            @include('components.admin.navigation')
            <header class="admin-welcome">
                <div>
                    <span class="section-kicker">Administration ENEAM</span>
                    <h1>Espace d’administration</h1>
                    <p>Pilotez les comptes, les référentiels et les paramètres de la bibliothèque numérique.</p>
                </div>
                <span class="account-badge">Administrateur</span>
            </header>

            <div class="admin-stat-grid" aria-label="Indicateurs de la plateforme">
                <article class="admin-stat-card"><span>Comptes</span><strong>{{ number_format($summary['users'], 0, ',', ' ') }}</strong><a href="{{ route('admin.users.index') }}">Gérer les comptes</a></article>
                <article class="admin-stat-card"><span>Étudiants</span><strong>{{ number_format($summary['students'], 0, ',', ' ') }}</strong><a href="{{ route('admin.users.index', ['role' => 'student']) }}">Voir les profils</a></article>
                <article class="admin-stat-card"><span>Mémoires</span><strong>{{ number_format($summary['theses'], 0, ',', ' ') }}</strong><small>tous statuts</small></article>
                <article class="admin-stat-card"><span>Analyses à suivre</span><strong>{{ number_format($summary['pending_analysis'], 0, ',', ' ') }}</strong><small>en attente ou en erreur</small></article>
                <article class="admin-stat-card"><span>Paiements confirmés</span><strong>{{ number_format($summary['confirmed_payments_xof'], 0, ',', ' ') }} F</strong><small>XOF cumulés</small></article>
            </div>

            <div class="admin-content-grid">
                <section class="admin-panel" aria-labelledby="thesis-status-title">
                    <div class="admin-panel-heading">
                        <div>
                            <span class="section-kicker">Suivi</span>
                            <h2 id="thesis-status-title">État des mémoires</h2>
                        </div>
                        <span class="admin-count">{{ $summary['theses'] }} dossier(s)</span>
                    </div>
                    @if (count($summary['theses_by_status']))
                        <ul class="admin-status-list">
                            @foreach ($summary['theses_by_status'] as $status => $total)
                                <li><span>{{ [
                                    'analysis_pending' => 'En attente d’analyse',
                                    'analysis_error' => 'Analyse en erreur',
                                    'awaiting_physical_review' => 'En attente du contrôle physique',
                                    'physical_received' => 'Réception physique confirmée',
                                    'published' => 'Publiés',
                                    'rejected' => 'Rejetés',
                                ][$status] ?? str_replace('_', ' ', ucfirst($status)) }}</span><strong>{{ $total }}</strong></li>
                            @endforeach
                        </ul>
                    @else
                        <div class="admin-empty">Aucun mémoire n’est encore enregistré.</div>
                    @endif
                    <p class="form-note">Contrôles physiques à traiter : {{ $summary['pending_physical_review'] }}.</p>
                </section>

                <section class="admin-panel" aria-labelledby="admin-shortcuts-title">
                    <div class="admin-panel-heading">
                        <div>
                            <span class="section-kicker">Gestion</span>
                            <h2 id="admin-shortcuts-title">Accès rapides</h2>
                        </div>
                    </div>
                    <div class="admin-shortcuts">
                        <a href="{{ route('admin.users.index', ['status' => 'suspended']) }}"><strong>Demandes en attente</strong><span>Examiner les comptes suspendus et demandes de rôle</span></a>
                        <a href="{{ route('admin.roles.index') }}"><strong>Rôles et permissions</strong><span>Contrôler les habilitations de chaque profil</span></a>
                        <a href="{{ route('admin.references.index') }}"><strong>Référentiels académiques</strong><span>Filières, niveaux et années de soutenance</span></a>
                        <a href="{{ route('admin.settings.index') }}"><strong>Paramètres de la plateforme</strong><span>Tarif externe, limite PDF et aperçu public</span></a>
                        <a href="{{ route('admin.activity-logs.index') }}"><strong>Journal d’activité</strong><span>Consulter les opérations sensibles et leur auteur</span></a>
                    </div>
                </section>
            </div>

            <aside class="admin-notice">
                <strong>Intégrations et données sensibles</strong>
                <p>Les clés de paiement et les secrets des fournisseurs ne sont jamais affichés dans cette interface. Configurez-les dans l’environnement de déploiement. L’analyse anti-plagiat reste en attente du choix du fournisseur institutionnel.</p>
            </aside>
        </div>
    </section>
@endsection
