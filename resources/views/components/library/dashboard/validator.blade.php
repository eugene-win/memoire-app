<section class="dashboard-page">
    <div class="container">
        <div class="dashboard-welcome">
            <div>
                <span class="section-kicker">Espace de validation</span>
                <h1>Bonjour, {{ auth()->user()->name }}</h1>
                <p>Traitez les dépôts, vérifiez l’analyse et consignez chaque décision.</p>
            </div>
            <span class="account-badge">Responsable ENEAM</span>
        </div>

        @include('components.library.dashboard.notifications')

        <div class="dashboard-grid">
            <article class="dashboard-card validator-queue">
                <div class="dashboard-card-heading">
                    <span class="benefit-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/>
                        </svg>
                    </span>
                    <div>
                        <h2>File des dépôts à traiter</h2>
                        <p>Dépôts en attente de résultat, d’analyse, de contrôle physique ou de décision.</p>
                    </div>
                </div>
                <div class="queue-notice">
                    <strong>Les décisions sont enregistrées avec l’identité du responsable et l’horodatage.</strong>
                    <span>Le résultat d’analyse doit provenir du fournisseur institutionnel. Son intégration automatique reste à configurer.</span>
                </div>
                @if ($theses->isEmpty())
                    <div class="dashboard-empty">
                        <strong>Aucun dépôt à traiter</strong>
                        <p>Les dossiers des étudiants apparaîtront ici après leur soumission.</p>
                    </div>
                @else
                    <div class="review-queue">
                        @foreach ($theses as $thesis)
                            @php($version = $thesis->currentVersion)
                            <article class="review-case">
                                <div class="review-case-top">
                                    <div>
                                        <span class="case-reference">{{ $thesis->reference }} · Version {{ $version->version_number }}</span>
                                        <h3>{{ $thesis->title }}</h3>
                                        <p>{{ $thesis->student->name }} · {{ $thesis->department }} · Soutenance {{ $thesis->defended_year }}</p>
                                    </div>
                                    <span class="review-status {{ in_array($thesis->status, ['awaiting_physical_review', 'physical_received']) ? 'status-ready' : 'status-waiting' }}">
                                        {{ match ($thesis->status) {
                                            'analysis_pending' => 'Analyse à traiter',
                                            'analysis_error' => 'Analyse en erreur',
                                            'awaiting_physical_review' => 'Contrôle physique attendu',
                                            'physical_received' => 'Décision attendue',
                                            default => $thesis->status,
                                        } }}
                                    </span>
                                </div>
                                <div class="similarity-summary">
                                    <div><small>Similitude</small><strong>{{ $version->similarity_percent !== null ? $version->similarity_percent.' %' : 'Non disponible' }}</strong></div>
                                    <div><small>Fichier</small><a href="{{ route('thesis-versions.viewer', $version) }}" target="_blank" rel="noopener">Ouvrir le PDF</a></div>
                                    <div><small>Soumis le</small><strong>{{ $version->created_at->format('d/m/Y H:i') }}</strong></div>
                                    @if ($version->analysis_provider)
                                        <div><small>Outil d’analyse</small><strong>{{ $version->analysis_provider }}</strong></div>
                                    @endif
                                    @if ($version->analysis_reference)
                                        <div><small>Référence du rapport</small><strong>{{ $version->analysis_reference }}</strong></div>
                                    @endif
                                    @if ($version->analysis_report_path)
                                        <div><small>Rapport anti-plagiat</small><a href="{{ route('responsable.thesis-analysis-reports.show', $version) }}" target="_blank" rel="noopener">Ouvrir le rapport PDF</a></div>
                                    @endif
                                    @if ($version->analysis_exclusions)
                                        <div><small>Exclusions appliquées</small><strong>{{ $version->analysis_exclusions }}</strong></div>
                                    @endif
                                </div>
                                @if (in_array($thesis->status, ['analysis_pending', 'analysis_error']))
                                    <form class="review-action-form" method="POST" action="{{ route('responsable.theses.analysis', $thesis) }}" enctype="multipart/form-data">
                                        @csrf
                                        <strong>Saisir le résultat fourni par l’outil d’analyse</strong>
                                        <div class="review-action-grid">
                                            <div class="field">
                                                <label for="provider-{{ $thesis->id }}">Fournisseur</label>
                                                <input id="provider-{{ $thesis->id }}" name="analysis_provider" value="{{ $version->analysis_provider }}" maxlength="255" required>
                                            </div>
                                            <div class="field">
                                                <label for="reference-{{ $thesis->id }}">Référence du rapport</label>
                                                <input id="reference-{{ $thesis->id }}" name="analysis_reference" value="{{ $version->analysis_reference }}" maxlength="255">
                                            </div>
                                            <div class="field">
                                                <label for="score-{{ $thesis->id }}">Taux de similitude (%)</label>
                                                <input id="score-{{ $thesis->id }}" name="similarity_percent" type="number" value="{{ $version->similarity_percent }}" min="0" max="100" step="0.01">
                                            </div>
                                            <div class="field">
                                                <label for="analysis-status-{{ $thesis->id }}">État de l’analyse</label>
                                                <select id="analysis-status-{{ $thesis->id }}" name="analysis_status" required>
                                                    <option value="completed">Résultat disponible</option>
                                                    <option value="error">Erreur technique</option>
                                                </select>
                                            </div>
                                            <div class="field review-error-note">
                                                <label for="analysis-note-{{ $thesis->id }}">Note requise en cas d’erreur</label>
                                                <textarea id="analysis-note-{{ $thesis->id }}" name="analysis_note" maxlength="2000">{{ $version->analysis_note }}</textarea>
                                            </div>
                                            <div class="field review-error-note">
                                                <label for="analysis-exclusions-{{ $thesis->id }}">Exclusions appliquées par l’outil (si indiquées)</label>
                                                <textarea id="analysis-exclusions-{{ $thesis->id }}" name="analysis_exclusions" maxlength="2000">{{ $version->analysis_exclusions }}</textarea>
                                            </div>
                                            <div class="field review-error-note">
                                                <label for="analysis-report-{{ $thesis->id }}">Rapport du fournisseur en PDF (20 Mo maximum, facultatif)</label>
                                                <input id="analysis-report-{{ $thesis->id }}" name="analysis_report" type="file" accept=".pdf,application/pdf">
                                                @if ($version->analysis_report_path)
                                                    <small>Un rapport est déjà joint; un nouveau fichier le remplacera.</small>
                                                @endif
                                            </div>
                                        </div>
                                        @if ($thesis->status === 'analysis_error')
                                            <p class="case-next-step">Dernier retour : {{ $version->analysis_note }}</p>
                                        @endif
                                        <button class="button" type="submit">Enregistrer l’analyse</button>
                                    </form>
                                @elseif ($thesis->status === 'awaiting_physical_review')
                                    <p class="case-next-step">Taux acceptable (≤ 20 %). Examinez le rapport du fournisseur puis comparez le document papier au PDF intégral avant de confirmer la réception.</p>
                                    <form class="review-action-form" method="POST" action="{{ route('responsable.theses.physical-receipt', $thesis) }}">
                                        @csrf
                                        <label class="deposit-rights form-note">
                                            <input type="checkbox" name="physical_checked" value="1" required>
                                            Je confirme avoir reçu le document papier et l’avoir comparé au PDF intégral.
                                        </label>
                                        <button class="button" type="submit">Confirmer la réception physique</button>
                                    </form>
                                @elseif ($thesis->status === 'physical_received')
                                    <p class="case-next-step">Réception confirmée le {{ $version->physical_received_at->format('d/m/Y H:i') }} par {{ $version->physicalReceiver->name ?? 'un responsable habilité' }}. Choisissez une décision après comparaison complète.</p>
                                    <form class="review-action-form" method="POST" action="{{ route('responsable.theses.decision', $thesis) }}">
                                        @csrf
                                        <div class="field">
                                            <label for="decision-{{ $thesis->id }}">Décision</label>
                                            <select id="decision-{{ $thesis->id }}" name="decision" required>
                                                <option value="publish">Publier — papier et PDF compatibles</option>
                                                <option value="reject">Rejeter — incompatibilité ou motif documentaire</option>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label for="decision-reason-{{ $thesis->id }}">Motif obligatoire en cas de rejet</label>
                                            <textarea id="decision-reason-{{ $thesis->id }}" name="reason" maxlength="2000"></textarea>
                                        </div>
                                        <label class="deposit-rights form-note">
                                            <input type="checkbox" name="physical_compatible" value="1">
                                            J’atteste la compatibilité physique pour confirmer la publication.
                                        </label>
                                        <button class="button" type="submit">Enregistrer la décision</button>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card-heading">
                    <span class="benefit-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3 3.5 7.5 12 12l8.5-4.5z"/><path d="M5 9v6l7 4 7-4V9M20.5 7.5v6"/>
                        </svg>
                    </span>
                    <div>
                        <h2>Étapes de validation</h2>
                        <p>Contrôles à effectuer avant toute publication.</p>
                    </div>
                </div>
                <ol class="review-steps">
                    <li><span>1</span><div><strong>Examiner l’analyse</strong><small>Lire le rapport; le taux acceptable est ≤ 20 %.</small></div></li>
                    <li><span>2</span><div><strong>Confirmer la réception physique</strong><small>Comparer le document papier au PDF intégral.</small></div></li>
                    <li><span>3</span><div><strong>Décider et consigner</strong><small>Publier si conforme, sinon rejeter avec un motif obligatoire.</small></div></li>
                </ol>
                <div class="review-decision-preview">
                    <strong>Décision attendue</strong>
                    <p>La publication est possible uniquement après analyse acceptable et confirmation de compatibilité physique. Le motif est obligatoire en cas de rejet humain.</p>
                    <small>Un taux supérieur à 20 % entraîne un rejet automatique. Une erreur d’analyse n’est jamais un rejet.</small>
                </div>
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card-heading">
                    <span class="benefit-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3v18M3 12h18"/><circle cx="12" cy="12" r="9"/>
                        </svg>
                    </span>
                    <div>
                        <h2>Autorisations de dépôt étudiant</h2>
                        <p>Accordez l’accès après vérification de l’éligibilité institutionnelle. L’autorisation reste active jusqu’à sa révocation.</p>
                    </div>
                </div>
                <form class="deposit-form" method="POST" action="{{ route('responsable.deposit-authorizations.store') }}">
                    @csrf
                    <div class="field">
                        <label for="authorization-matricule">Matricule étudiant *</label>
                        <input id="authorization-matricule" name="matricule" value="{{ old('matricule') }}" maxlength="255" required>
                    </div>
                    <div class="field">
                        <label for="authorization-reason">Motif de l’autorisation *</label>
                        <input id="authorization-reason" name="reason" value="{{ old('reason') }}" minlength="10" maxlength="2000" required>
                    </div>
                    <div class="deposit-form-actions">
                        <button class="button" type="submit">Accorder l’autorisation</button>
                    </div>
                </form>
                <div class="submission-list">
                    <h3>Étudiants autorisés</h3>
                    @forelse ($authorizedStudents as $authorizedStudent)
                        <article class="submission-item">
                            <div class="submission-item-heading">
                                <div>
                                    <h3>{{ $authorizedStudent->name }}</h3>
                                    <p>Matricule : {{ $authorizedStudent->matricule }}</p>
                                </div>
                                <span class="review-status status-ready">Autorisation active</span>
                            </div>
                            <form class="review-action-form" method="POST" action="{{ route('responsable.students.deposit-authorization.destroy', $authorizedStudent) }}">
                                @csrf
                                <div class="field">
                                    <label for="revoke-reason-{{ $authorizedStudent->id }}">Motif de révocation *</label>
                                    <input id="revoke-reason-{{ $authorizedStudent->id }}" name="reason" minlength="10" maxlength="2000" required>
                                </div>
                                <button class="button button-outline" type="submit">Révoquer l’autorisation</button>
                            </form>
                        </article>
                    @empty
                        <div class="dashboard-empty dashboard-empty-compact">
                            <strong>Aucun étudiant autorisé pour le moment</strong>
                            <p>Les autorisations actives apparaîtront ici.</p>
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card-heading">
                    <span class="benefit-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 5h16v15H4z"/><path d="M8 9h8M8 13h8M8 17h4"/>
                        </svg>
                    </span>
                    <div>
                        <h2>Importer une archive</h2>
                        <p>Les archives ne sont publiées qu’après contrôle du document et confirmation des droits de diffusion.</p>
                    </div>
                </div>
                <form class="deposit-form" id="archive-import-form" method="POST" action="{{ route('responsable.archives.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="field">
                        <label for="archive-title">Titre du mémoire *</label>
                        <input id="archive-title" name="title" value="{{ old('title') }}" maxlength="255" required>
                    </div>
                    <div class="field">
                        <label for="archive-author">Nom et prénom de l’auteur *</label>
                        <input id="archive-author" name="author_name" value="{{ old('author_name') }}" maxlength="255" required>
                    </div>
                    <div class="field">
                        <label for="archive-matricule">Matricule historique *</label>
                        <input id="archive-matricule" name="matricule" value="{{ old('matricule') }}" maxlength="255" required>
                    </div>
                    <div class="field">
                        <label for="archive-department">Filière *</label>
                        <select id="archive-department" name="department" required>
                            <option value="">Sélectionner une filière</option>
                            @foreach (['Finance et comptabilité', 'Gestion des entreprises', 'Marketing et commerce', 'Informatique de gestion', 'Économie appliquée'] as $department)
                                <option value="{{ $department }}" @selected(old('department') === $department)>{{ $department }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="archive-year">Année de soutenance *</label>
                        <input id="archive-year" name="defended_year" type="number" value="{{ old('defended_year') }}" min="1900" max="{{ now()->year }}" required>
                    </div>
                    <div class="field">
                        <label for="archive-supervisor">Encadreur</label>
                        <input id="archive-supervisor" name="supervisor" value="{{ old('supervisor') }}" maxlength="255">
                    </div>
                    <div class="field deposit-form-full">
                        <label for="archive-source">Origine de l’archive *</label>
                        <input id="archive-source" name="source" value="{{ old('source') }}" maxlength="255" placeholder="Ex. fonds papier de la bibliothèque" required>
                    </div>
                    <div class="field deposit-form-full">
                        <label for="archive-control-result">Résultat du contrôle documentaire *</label>
                        <textarea id="archive-control-result" name="control_result" maxlength="2000" required>{{ old('control_result') }}</textarea>
                    </div>
                    <div class="field deposit-file-field deposit-form-full">
                        <label for="archive-file">PDF intégral (50 Mo maximum) *</label>
                        <input id="archive-file" name="document" type="file" accept=".pdf,application/pdf" required>
                    </div>
                    <label class="deposit-rights form-note">
                        <input type="checkbox" name="physical_checked" value="1" required>
                        Je confirme avoir contrôlé le document physique et sa concordance avec le PDF.
                    </label>
                    <label class="deposit-rights form-note">
                        <input type="checkbox" name="diffusion_rights_confirmed" value="1" required>
                        Je confirme que les droits autorisent la diffusion de cette archive.
                    </label>
                    <div class="deposit-form-actions">
                        <button class="button" type="submit">Importer et publier l’archive</button>
                    </div>
                </form>

                <div class="submission-list">
                    <h3>Imports récents</h3>
                    @forelse ($archiveImports as $archiveImport)
                        <article class="submission-item">
                            <div class="submission-item-heading">
                                <div>
                                    <span class="case-reference">{{ $archiveImport->thesis->reference }} · {{ $archiveImport->imported_at->format('d/m/Y H:i') }}</span>
                                    <h3>{{ $archiveImport->thesis->title }}</h3>
                                    <p>{{ $archiveImport->thesis->author_name }} · Source : {{ $archiveImport->source }} · Importée par {{ $archiveImport->responsible->name }}</p>
                                </div>
                            </div>
                            <p class="decision-note">{{ $archiveImport->control_result }}</p>
                        </article>
                    @empty
                        <div class="dashboard-empty dashboard-empty-compact">
                            <strong>Aucune archive importée</strong>
                            <p>Les imports contrôlés apparaîtront ici.</p>
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="dashboard-card dashboard-notice">
                <span class="notice-symbol" aria-hidden="true">!</span>
                <div>
                    <h2>Règles de traitement</h2>
                    <p>Un taux supérieur à 20 % entraîne un rejet automatique. Un taux inférieur ou égal à 20 % ne publie pas le mémoire à lui seul : le contrôle physique et la décision du responsable restent obligatoires. Une erreur d’analyse n’est pas un rejet.</p>
                </div>
            </article>
        </div>
    </div>
</section>
