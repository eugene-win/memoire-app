<section class="section" id="catalogue">
                <div class="container">
                    <div class="section-heading">
                        <div>
                            <span class="section-kicker">La bibliothèque numérique</span>
                            <h2>Explorez les mémoires publiés</h2>
                            <p>Parcourez les travaux académiques, affinez votre recherche et ouvrez la fiche d’un mémoire pour en consulter l’aperçu.</p>
                        </div>
                    </div>

                    <div class="search-panel" role="search" aria-label="Rechercher dans le catalogue">
                        <div class="search-row">
                            <label class="search-input-wrap" for="catalog-search">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>
                                </svg>
                                <input id="catalog-search" type="search" placeholder="Rechercher par thème, titre, auteur ou mot-clé…" autocomplete="off">
                            </label>
                            <button class="button" id="search-button" type="button">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>
                                </svg>
                                Rechercher
                            </button>
                        </div>
                        <div class="filter-row">
                            <div class="field">
                                <label for="department-filter">Filière</label>
                                <select id="department-filter">
                                    <option value="">Toutes les filières</option>
                                    <option value="Finance et comptabilité">Finance et comptabilité</option>
                                    <option value="Gestion des entreprises">Gestion des entreprises</option>
                                    <option value="Marketing et commerce">Marketing et commerce</option>
                                    <option value="Informatique de gestion">Informatique de gestion</option>
                                    <option value="Économie appliquée">Économie appliquée</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="year-filter">Année de soutenance</label>
                                <select id="year-filter">
                                    <option value="">Toutes les années</option>
                                    <option value="2025">2025</option>
                                    <option value="2024">2024</option>
                                    <option value="2023">2023</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="sort-select">Trier les résultats</label>
                                <select id="sort-select">
                                    <option value="newest">Plus récents</option>
                                    <option value="oldest">Plus anciens</option>
                                    <option value="title">Titre (A à Z)</option>
                                </select>
                            </div>
                            <div class="filter-actions">
                                <button class="button button-outline" id="reset-filters" type="button">Réinitialiser</button>
                            </div>
                        </div>
                    </div>

                    <div class="catalog-toolbar">
                        <div>
                            <h3>Mémoires récemment publiés</h3>
                            <div class="catalog-count" id="results-count" aria-live="polite"></div>
                        </div>
                        <span class="section-kicker">{{ $publishedTheses->isEmpty() ? 'Notices de démonstration' : 'Mémoires publiés' }}</span>
                    </div>

                    <div class="catalog-grid" id="catalog-grid">
                        @if ($publishedTheses->isNotEmpty())
                            @foreach ($publishedTheses as $thesis)
                                @php
                                    $hasGrantedAccess = in_array($thesis->id, $accessibleThesisIds, true);
                                    $isFreeReader = auth()->check() && in_array(auth()->user()->role, ['student', 'validator', 'library_manager'], true);
                                    $canViewThesis = $isFreeReader || $hasGrantedAccess;
                                    $canStartPayment = auth()->check() && auth()->user()->role === 'external_reader' && ! $hasGrantedAccess;
                                    $paymentConfigured = filled(config('services.kkiapay.public_key'))
                                        && filled(config('services.kkiapay.private_key'))
                                        && filled(config('services.kkiapay.secret'));
                                @endphp
                                <article class="thesis-card"
                                    data-real="true"
                                    data-viewer-url="{{ route('thesis-versions.viewer', $thesis->currentVersion) }}"
                                    data-can-view="{{ $canViewThesis ? 'true' : 'false' }}"
                                    data-can-pay="{{ $canStartPayment && $paymentConfigured ? 'true' : 'false' }}"
                                    data-payment-unconfigured="{{ $canStartPayment && ! $paymentConfigured ? 'true' : 'false' }}"
                                    data-payment-url="{{ $canStartPayment ? route('payments.start', $thesis) : '' }}"
                                    data-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
                                    data-title="{{ $thesis->title }}"
                                    data-author="{{ $thesis->author_name }}"
                                    data-department="{{ $thesis->department }}"
                                    data-department-label="{{ $thesis->department }}"
                                    data-year="{{ $thesis->defended_year }}"
                                    data-search="{{ $thesis->title }} {{ $thesis->author_name }} {{ $thesis->department }} {{ $thesis->supervisor }}"
                                    data-abstract="Résumé non renseigné.">
                                    <div class="card-top">
                                        <span class="document-thumb" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 12h6M9 16h6"/>
                                            </svg>
                                        </span>
                                        <div class="card-content">
                                            <span class="card-category">{{ $thesis->department }}</span>
                                            <h4>{{ $thesis->title }}</h4>
                                            <div class="author-line">{{ $thesis->author_name }}</div>
                                        </div>
                                    </div>
                                    <div class="card-meta">
                                        <span class="meta-item">{{ $thesis->defended_year }}</span>
                                        <span class="meta-item">Mémoire ENEAM</span>
                                    </div>
                                    <div class="card-bottom">
                                        <span class="status-pill">Publié</span>
                                        <button class="text-button" type="button" data-thesis>Voir la fiche</button>
                                    </div>
                                </article>
                            @endforeach
                        @else
                        <article class="thesis-card"
                            data-title="La digitalisation du contrôle de gestion dans les PME béninoises"
                            data-author="Notice de démonstration"
                            data-department="Gestion des entreprises"
                            data-department-label="Gestion des entreprises"
                            data-year="2025"
                            data-search="digitalisation contrôle gestion PME béninoises transformation numérique entreprise pilotage"
                            data-abstract="Cette notice de démonstration illustre la présentation d’un mémoire sur l’évolution des outils de pilotage et l’adoption de solutions numériques au sein des petites et moyennes entreprises béninoises.">
                            <div class="card-top">
                                <span class="document-thumb" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 12h6M9 16h6"/>
                                    </svg>
                                </span>
                                <div class="card-content">
                                    <span class="card-category">Gestion des entreprises</span>
                                    <h4>La digitalisation du contrôle de gestion dans les PME béninoises</h4>
                                    <div class="author-line">Notice de démonstration</div>
                                </div>
                            </div>
                            <div class="card-meta">
                                <span class="meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>
                                    </svg>
                                    2025
                                </span>
                                <span class="meta-item">Mémoire de Licence</span>
                            </div>
                            <div class="card-bottom">
                                <span class="status-pill">Publié</span>
                                <button class="text-button" type="button" data-thesis>
                                    Voir la fiche
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M13 6l6 6-6 6"/>
                                    </svg>
                                </button>
                            </div>
                        </article>

                        <article class="thesis-card"
                            data-title="Inclusion financière et accès au crédit des petites entreprises"
                            data-author="Notice de démonstration"
                            data-department="Finance et comptabilité"
                            data-department-label="Finance et comptabilité"
                            data-year="2025"
                            data-search="inclusion financière crédit petites entreprises banque financement finance"
                            data-abstract="Cette notice de démonstration présente un travail portant sur les mécanismes d’inclusion financière et les conditions d’accès des petites entreprises aux services de crédit.">
                            <div class="card-top">
                                <span class="document-thumb gold" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 12h6M9 16h6"/>
                                    </svg>
                                </span>
                                <div class="card-content">
                                    <span class="card-category">Finance et comptabilité</span>
                                    <h4>Inclusion financière et accès au crédit des petites entreprises</h4>
                                    <div class="author-line">Notice de démonstration</div>
                                </div>
                            </div>
                            <div class="card-meta">
                                <span class="meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>
                                    </svg>
                                    2025
                                </span>
                                <span class="meta-item">Mémoire de Licence</span>
                            </div>
                            <div class="card-bottom">
                                <span class="status-pill">Publié</span>
                                <button class="text-button" type="button" data-thesis>Voir la fiche
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M13 6l6 6-6 6"/>
                                    </svg>
                                </button>
                            </div>
                        </article>

                        <article class="thesis-card"
                            data-title="Stratégies de fidélisation de la clientèle dans le commerce de détail"
                            data-author="Notice de démonstration"
                            data-department="Marketing et commerce"
                            data-department-label="Marketing et commerce"
                            data-year="2024"
                            data-search="stratégies fidélisation clientèle commerce détail marketing relation client"
                            data-abstract="Cette notice de démonstration illustre un mémoire consacré aux pratiques de fidélisation, à l’expérience client et aux leviers de différenciation dans le commerce de détail.">
                            <div class="card-top">
                                <span class="document-thumb violet" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 12h6M9 16h6"/>
                                    </svg>
                                </span>
                                <div class="card-content">
                                    <span class="card-category">Marketing et commerce</span>
                                    <h4>Stratégies de fidélisation de la clientèle dans le commerce de détail</h4>
                                    <div class="author-line">Notice de démonstration</div>
                                </div>
                            </div>
                            <div class="card-meta">
                                <span class="meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>
                                    </svg>
                                    2024
                                </span>
                                <span class="meta-item">Mémoire de Licence</span>
                            </div>
                            <div class="card-bottom">
                                <span class="status-pill">Publié</span>
                                <button class="text-button" type="button" data-thesis>Voir la fiche
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M13 6l6 6-6 6"/>
                                    </svg>
                                </button>
                            </div>
                        </article>

                        <article class="thesis-card"
                            data-title="Système d’information et qualité du service aux usagers"
                            data-author="Notice de démonstration"
                            data-department="Informatique de gestion"
                            data-department-label="Informatique de gestion"
                            data-year="2024"
                            data-search="système information qualité service usagers informatique gestion administration"
                            data-abstract="Cette notice de démonstration présente un sujet consacré à la contribution des systèmes d’information à la qualité du service rendu aux usagers.">
                            <div class="card-top">
                                <span class="document-thumb green" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 12h6M9 16h6"/>
                                    </svg>
                                </span>
                                <div class="card-content">
                                    <span class="card-category">Informatique de gestion</span>
                                    <h4>Système d’information et qualité du service aux usagers</h4>
                                    <div class="author-line">Notice de démonstration</div>
                                </div>
                            </div>
                            <div class="card-meta">
                                <span class="meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>
                                    </svg>
                                    2024
                                </span>
                                <span class="meta-item">Mémoire de Licence</span>
                            </div>
                            <div class="card-bottom">
                                <span class="status-pill">Publié</span>
                                <button class="text-button" type="button" data-thesis>Voir la fiche
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M13 6l6 6-6 6"/>
                                    </svg>
                                </button>
                            </div>
                        </article>

                        <article class="thesis-card"
                            data-title="Entrepreneuriat des jeunes et développement économique local"
                            data-author="Notice de démonstration"
                            data-department="Économie appliquée"
                            data-department-label="Économie appliquée"
                            data-year="2023"
                            data-search="entrepreneuriat jeunes développement économique local économie"
                            data-abstract="Cette notice de démonstration met en avant une recherche sur la place de l’entrepreneuriat des jeunes dans les dynamiques de développement économique local.">
                            <div class="card-top">
                                <span class="document-thumb gold" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 12h6M9 16h6"/>
                                    </svg>
                                </span>
                                <div class="card-content">
                                    <span class="card-category">Économie appliquée</span>
                                    <h4>Entrepreneuriat des jeunes et développement économique local</h4>
                                    <div class="author-line">Notice de démonstration</div>
                                </div>
                            </div>
                            <div class="card-meta">
                                <span class="meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>
                                    </svg>
                                    2023
                                </span>
                                <span class="meta-item">Mémoire de Licence</span>
                            </div>
                            <div class="card-bottom">
                                <span class="status-pill">Publié</span>
                                <button class="text-button" type="button" data-thesis>Voir la fiche
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M13 6l6 6-6 6"/>
                                    </svg>
                                </button>
                            </div>
                        </article>

                        <article class="thesis-card"
                            data-title="Gestion des risques et performance des institutions de microfinance"
                            data-author="Notice de démonstration"
                            data-department="Finance et comptabilité"
                            data-department-label="Finance et comptabilité"
                            data-year="2023"
                            data-search="gestion risques performance institutions microfinance finance"
                            data-abstract="Cette notice de démonstration illustre une recherche sur les pratiques de gestion des risques et leurs liens avec la performance des institutions de microfinance.">
                            <div class="card-top">
                                <span class="document-thumb" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 12h6M9 16h6"/>
                                    </svg>
                                </span>
                                <div class="card-content">
                                    <span class="card-category">Finance et comptabilité</span>
                                    <h4>Gestion des risques et performance des institutions de microfinance</h4>
                                    <div class="author-line">Notice de démonstration</div>
                                </div>
                            </div>
                            <div class="card-meta">
                                <span class="meta-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>
                                    </svg>
                                    2023
                                </span>
                                <span class="meta-item">Mémoire de Licence</span>
                            </div>
                            <div class="card-bottom">
                                <span class="status-pill">Publié</span>
                                <button class="text-button" type="button" data-thesis>Voir la fiche
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14M13 6l6 6-6 6"/>
                                    </svg>
                                </button>
                            </div>
                        </article>
                        @endif
                        <div class="empty-state" id="empty-state" hidden>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"/><path d="m20 20-4-4M8 11h6"/>
                            </svg>
                            <strong>Aucun mémoire ne correspond à votre recherche</strong>
                            <p>Essayez un autre mot-clé ou réinitialisez les filtres.</p>
                        </div>
                    </div>

                    @if ($publishedTheses->isEmpty())
                    <div class="catalog-note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>
                        </svg>
                        <span>Les notices affichées sont des exemples de présentation. Le catalogue réel affichera uniquement les mémoires publiés et autorisés à la consultation.</span>
                    </div>
                    @endif
                </div>
            </section>
