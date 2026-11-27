<dialog class="modal" id="deposit-dialog" aria-labelledby="deposit-title">
            <div class="modal-head">
                <div>
                    <h2 id="deposit-title">Conditions de dépôt</h2>
                    <p>Le dépôt se prépare depuis l’espace étudiant ENEAM.</p>
                </div>
                <button class="modal-close" type="button" aria-label="Fermer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="upload-note">Seuls les étudiants L3 ayant soutenu et disposant d’une autorisation de dépôt active peuvent soumettre un mémoire. Le PDF est ensuite analysé, contrôlé avec le document physique, puis publié ou rejeté avec un motif.</div>
                <ol class="review-steps deposit-conditions">
                    <li><span>1</span><div><strong>Vérifier l’éligibilité</strong><small>Compte étudiant ENEAM, soutenance et autorisation active.</small></div></li>
                    <li><span>2</span><div><strong>Préparer le dossier</strong><small>Métadonnées complètes et PDF valide de 5 Mo maximum.</small></div></li>
                    <li><span>3</span><div><strong>Suivre le traitement</strong><small>Analyse de similitude, contrôle physique, décision motivée.</small></div></li>
                </ol>
                <a class="button" href="{{ route('login') }}">Se connecter comme étudiant</a>
            </div>
        </dialog>
