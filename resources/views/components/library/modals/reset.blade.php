<dialog class="modal" id="reset-dialog" aria-labelledby="reset-title" @if (session('password-reset-status') || $errors->passwordReset->any()) data-auto-open @endif>
            <div class="modal-head">
                <div>
                    <h2 id="reset-title">Réinitialiser le mot de passe</h2>
                    <p>Indiquez l’adresse e-mail associée à votre compte.</p>
                </div>
                <button class="modal-close" type="button" aria-label="Fermer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="modal-body">
                @if (session('password-reset-status'))
                    <p class="form-note" role="status">{{ session('password-reset-status') }}</p>
                @endif
                @if ($errors->passwordReset->any())
                    <div class="form-errors" role="alert">
                        @foreach ($errors->passwordReset->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                <form class="modal-form" id="reset-form" method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="field">
                        <label for="reset-email">Adresse e-mail</label>
                        <input id="reset-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                    </div>
                    <p class="form-note">Si un compte est associé à cette adresse, vous recevrez un lien de réinitialisation.</p>
                    <button class="button" type="submit">Continuer</button>
                </form>
            </div>
        </dialog>
