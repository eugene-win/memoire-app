<section class="dashboard-page">
    <div class="container">
        <div class="dashboard-welcome">
            <div>
                <span class="section-kicker">Espace étudiant ENEAM</span>
                <h1>Bonjour, {{ auth()->user()->name }}</h1>
                <p>Accédez gratuitement aux mémoires publiés et suivez vos dépôts.</p>
            </div>
            <span class="account-badge">Étudiant ENEAM</span>
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
                        <h2>Bibliothèque des mémoires</h2>
                        <p>Accès intégral gratuit aux mémoires publiés pour les étudiants ENEAM vérifiés.</p>
                    </div>
                </div>
                <div class="dashboard-empty">
                    <strong>Explorez les travaux de votre communauté</strong>
                    <p>Recherchez par titre, auteur, filière, année ou mot-clé.</p>
                    <a class="button" href="{{ url('/#catalogue') }}">Explorer le catalogue</a>
                </div>
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
                        <p>Retrouvez les mémoires consultés récemment.</p>
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
                        <p>Les mémoires consultés apparaîtront ici.</p>
                    </div>
                @endforelse
            </article>

            <article class="dashboard-card student-submissions">
                <div class="dashboard-card-heading">
                    <span class="benefit-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 12h6M9 16h6"/>
                        </svg>
                    </span>
                    <div>
                        <h2>Mes dépôts</h2>
                        <p>Historique, versions, rapport et décisions.</p>
                    </div>
                </div>
                @if ($theses->isEmpty())
                    <div class="dashboard-empty dashboard-empty-compact">
                        <strong>Aucun dépôt enregistré</strong>
                        <p>Les dossiers soumis apparaîtront ici avec leur statut et, en cas de rejet, le motif et l’accès au redépôt.</p>
                    </div>
                @else
                    <div class="submission-list">
                        @foreach ($theses as $thesis)
                            @php
                                $statusLabels = [
                                    'analysis_pending' => 'En attente d’analyse',
                                    'analysis_error' => 'Analyse en erreur · en attente de reprise',
                                    'awaiting_physical_review' => 'En attente du contrôle physique',
                                    'physical_received' => 'Document physique reçu · décision en attente',
                                    'published' => 'Publié',
                                    'rejected' => 'Rejeté · nouvelle version possible',
                                ];
                                $currentVersion = $thesis->versions->first();
                            @endphp
                            <article class="submission-item">
                                <div class="submission-item-heading">
                                    <div>
                                        <span class="case-reference">{{ $thesis->reference }}</span>
                                        <h3>{{ $thesis->title }}</h3>
                                        <p>{{ $thesis->department }} · Soutenance {{ $thesis->defended_year }}</p>
                                    </div>
                                    <span class="review-status {{ $thesis->status === 'published' ? 'status-ready' : ($thesis->status === 'rejected' ? 'status-rejected' : 'status-waiting') }}">
                                        {{ $statusLabels[$thesis->status] ?? $thesis->status }}
                                    </span>
                                </div>
                                <div class="submission-version">
                                    @foreach ($thesis->versions as $version)
                                        <div>
                                            <strong>Version {{ $version->version_number }}</strong>
                                            <span>{{ $version->metadata_snapshot['title'] ?? $thesis->title }}</span>
                                            <span>{{ number_format($version->file_size / 1048576, 1) }} Mo</span>
                                            @if ($version->similarity_percent !== null)
                                                <span>Similitude {{ $version->similarity_percent }} %</span>
                                            @endif
                                            <a href="{{ route('thesis-versions.viewer', $version) }}" target="_blank" rel="noopener">Ouvrir mon PDF</a>
                                        </div>
                                    @endforeach
                                </div>
                                @foreach ($thesis->decisions as $decision)
                                    @if ($decision->reason)
                                        <p class="decision-note"><strong>{{ str_replace('_', ' ', ucfirst($decision->type)) }} · V{{ $decision->version->version_number ?? '?' }} · {{ $decision->created_at->format('d/m/Y H:i') }}{{ $decision->actor ? ' · '.$decision->actor->name : '' }} :</strong> {{ $decision->reason }}</p>
                                    @endif
                                @endforeach
                                @if ($thesis->status === 'rejected' && auth()->user()->deposit_authorized)
                                    <a class="text-button" href="#deposit-form">Déposer une version corrigée</a>
                                @endif
                                @if ($currentVersion?->analysis_status === 'error')
                                    <p class="form-note">L’analyse est en erreur technique. Cela ne constitue pas un rejet; le dossier attend une nouvelle analyse.</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
                <div class="workflow-preview">
                    <span class="workflow-label">Étapes de suivi</span>
                    <ol class="workflow-track">
                        <li><span>1</span><strong>Soumis</strong></li>
                        <li><span>2</span><strong>Analyse de similitude</strong></li>
                        <li><span>3</span><strong>Contrôle physique</strong></li>
                        <li><span>4</span><strong>Publié ou rejeté</strong></li>
                    </ol>
                    <p>Après un rejet motivé, vous pourrez déposer une nouvelle version sans perdre l’historique précédent.</p>
                </div>
            </article>

            <article class="dashboard-card dashboard-deposit" id="deposer">
                <div class="dashboard-card-heading">
                    <span class="benefit-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 16V4M7 9l5-5 5 5"/><path d="M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/>
                        </svg>
                    </span>
                    <div>
                        <h2>Déposer mon mémoire</h2>
                        <p>Dépôt réservé aux étudiants de Licence 3 autorisés.</p>
                    </div>
                </div>
                @if (auth()->user()->deposit_authorized)
                    <div class="dashboard-callout dashboard-callout-success">
                        <strong>Votre autorisation de dépôt est active</strong>
                        <p>Complétez les informations du mémoire et joignez le PDF intégral (50 Mo maximum).</p>
                        <a class="button" href="#deposit-form">Ouvrir le formulaire de dépôt</a>
                    </div>
                @else
                    <div class="dashboard-callout">
                        <strong>Autorisation de dépôt non confirmée</strong>
                        <p>Le dépôt est réservé aux étudiants L3 ayant soutenu et obtenu une autorisation active de l’ENEAM. Contactez votre responsable de filière pour faire vérifier votre éligibilité.</p>
                        <span class="status-pill status-pending">En attente de vérification</span>
                    </div>
                @endif
            </article>

            @if (auth()->user()->deposit_authorized)
                <article class="dashboard-card deposit-form-card">
                    <div class="dashboard-card-heading">
                        <span class="benefit-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 16V4M7 9l5-5 5 5"/><path d="M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/>
                            </svg>
                        </span>
                        <div>
                            <h2>Formulaire de dépôt</h2>
                            <p>Les champs marqués d’un astérisque sont obligatoires.</p>
                        </div>
                    </div>
                    <form class="deposit-form" id="deposit-form" method="POST" action="{{ route('deposits.store') }}" enctype="multipart/form-data">
                        @csrf
                        @if ($theses->where('status', 'rejected')->isNotEmpty())
                            <div class="field deposit-form-full">
                                <label for="thesis-id">Version corrigée d’un dépôt rejeté (facultatif)</label>
                                <select id="thesis-id" name="thesis_id">
                                    <option value="">Nouveau dépôt</option>
                                    @foreach ($theses->where('status', 'rejected') as $rejectedThesis)
                                        <option value="{{ $rejectedThesis->id }}">Version corrective · {{ $rejectedThesis->reference }} — {{ $rejectedThesis->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="field">
                            <label for="thesis-title">Titre du mémoire *</label>
                            <input id="thesis-title" name="title" type="text" maxlength="255" required>
                        </div>
                        <div class="field">
                            <label for="thesis-author">Nom et prénom *</label>
                            <input id="thesis-author" name="author" type="text" value="{{ auth()->user()->name }}" readonly required>
                        </div>
                        <div class="field">
                            <label for="thesis-matricule">Matricule *</label>
                            <input id="thesis-matricule" name="matricule" type="text" value="{{ auth()->user()->matricule }}" readonly required>
                        </div>
                        <div class="field">
                            <label for="thesis-department">Filière *</label>
                            <select id="thesis-department" name="department" required>
                                <option value="">Sélectionner une filière</option>
                                <option>Finance et comptabilité</option>
                                <option>Gestion des entreprises</option>
                                <option>Marketing et commerce</option>
                                <option>Informatique de gestion</option>
                                <option>Économie appliquée</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="thesis-year">Année de soutenance *</label>
                            <select id="thesis-year" name="defended_year" required>
                                <option value="">Sélectionner une année</option>
                                @for ($year = now()->year; $year >= now()->year - 5; $year--)
                                    <option>{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="field">
                            <label for="thesis-supervisor">Encadreur</label>
                            <input id="thesis-supervisor" name="supervisor" type="text" maxlength="255">
                        </div>
                        <div class="field deposit-file-field">
                            <label for="thesis-file">Fichier PDF intégral (50 Mo maximum) *</label>
                            <input class="file-input" id="thesis-file" name="document" type="file" accept=".pdf,application/pdf" required>
                            <p class="form-note" id="file-name">Aucun fichier sélectionné</p>
                        </div>
                        <label class="deposit-rights form-note">
                            <input type="checkbox" name="rights" required>
                            Je confirme être autorisé à déposer ce document et accepter les conditions de diffusion applicables.
                        </label>
                        <div class="deposit-form-actions">
                            <button class="button" type="submit">Soumettre mon mémoire</button>
                            <p class="form-note">Le fichier est conservé dans le stockage privé. Le responsable enregistrera le résultat reçu du fournisseur d’analyse anti-plagiat.</p>
                        </div>
                    </form>
                </article>
            @endif

            <article class="dashboard-card dashboard-notice">
                <span class="notice-symbol" aria-hidden="true">i</span>
                <div>
                    <h2>Votre compte étudiant</h2>
                    <p>Matricule : <strong>{{ auth()->user()->matricule }}</strong>. Vos consultations des mémoires publiés sont gratuites; les nouvelles soumissions sont soumises aux conditions d’éligibilité L3.</p>
                </div>
            </article>
        </div>
    </div>
</section>
