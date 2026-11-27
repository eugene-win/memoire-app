<dialog class="modal" id="detail-dialog" aria-labelledby="detail-title">
            <div class="modal-head">
                <div>
                    <span class="section-kicker">Fiche du mémoire</span>
                    <h2 class="detail-title" id="detail-title"></h2>
                </div>
                <button class="modal-close" type="button" aria-label="Fermer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="detail-meta">
                    <div><small>Auteur</small><strong id="detail-author"></strong></div>
                    <div><small>Filière</small><strong id="detail-department"></strong></div>
                    <div><small>Année de soutenance</small><strong id="detail-year"></strong></div>
                    <div><small>Statut</small><strong>Publié</strong></div>
                </div>
                <p class="detail-abstract" id="detail-abstract"></p>
                <p class="form-note" id="detail-access-note">L’aperçu public est limité. L’accès intégral dépend de votre statut étudiant ou d’un paiement confirmé.</p>
                <p class="form-note" id="payment-configuration-note" hidden>Le paiement KKiaPay n’est pas encore configuré dans cet environnement.</p>
                <div class="detail-actions">
                    <button class="button button-outline" id="open-preview" type="button">Lire l’aperçu</button>
                    <a class="button button-outline" id="open-document" target="_blank" rel="noopener" hidden>Consulter le mémoire</a>
                    <button class="button" id="full-access" type="button">Accès intégral</button>
                    <form id="start-payment-form" method="POST" hidden>
                        @csrf
                        <button class="button" id="start-payment" type="submit">Payer 1 000 F CFA avec KKiaPay</button>
                    </form>
                </div>
            </div>
        </dialog>
