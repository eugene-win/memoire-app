@extends('layouts.library')

@section('title', 'Gestion des comptes · Administration ENEAM')

@section('content')
    <section class="admin-page">
        <div class="container">
            @include('components.admin.navigation')
            <header class="admin-welcome">
                <div>
                    <span class="section-kicker">Administration ENEAM</span>
                    <h1>Comptes et profils étudiants</h1>
                    <p>Examinez les demandes, gérez les rôles et complétez les informations institutionnelles.</p>
                </div>
                <span class="account-badge">{{ $users->total() }} compte(s)</span>
            </header>

            <div class="admin-feedback" role="status" aria-live="polite" hidden></div>
            <section class="admin-panel">
                <form class="admin-filters" method="GET" action="{{ route('admin.users.index') }}">
                    <div class="field"><label for="user-search">Recherche</label><input id="user-search" name="q" value="{{ request('q') }}" placeholder="Nom, e-mail ou matricule"></div>
                    <div class="field"><label for="user-status">Statut</label><select id="user-status" name="status"><option value="">Tous</option>@foreach (['active' => 'Actif', 'suspended' => 'Suspendu', 'disabled' => 'Désactivé'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="field"><label for="user-role">Profil</label><select id="user-role" name="role"><option value="">Tous</option>@foreach ($roles as $role)<option value="{{ $role->slug }}" @selected(request('role') === $role->slug)>{{ $role->name }}</option>@endforeach</select></div>
                    <div class="admin-filter-actions"><button class="button" type="submit">Filtrer</button><a class="button button-outline" href="{{ route('admin.users.index') }}">Effacer</a></div>
                </form>

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Compte</th><th>Profil demandé</th><th>Rôle actif</th><th>Statut</th><th>Actions</th></tr></thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td><strong>{{ $user->name }}</strong><small>{{ $user->email }}{{ $user->matricule ? ' · '.$user->matricule : '' }}</small></td>
                                    <td>{{ $user->requested_role ? ($roles->firstWhere('slug', $user->requested_role)?->name ?? $user->requested_role) : '—' }}</td>
                                    <td>{{ $roles->firstWhere('slug', $user->role)?->name ?? $user->role }}</td>
                                    <td><span class="admin-status admin-status-{{ $user->account_status }}">{{ ['active' => 'Actif', 'suspended' => 'Suspendu', 'disabled' => 'Désactivé'][$user->account_status] ?? $user->account_status }}</span></td>
                                    <td>
                                        <details class="admin-details">
                                            <summary>Gérer</summary>
                                            <div class="admin-detail-content">
                                                <form class="admin-action-form" method="POST" action="{{ route('admin.users.update', $user) }}" data-admin-form>
                                                    @csrf @method('PATCH')
                                                    <h3>Informations du compte</h3>
                                                    <div class="field"><label for="first-name-{{ $user->id }}">Prénom</label><input id="first-name-{{ $user->id }}" name="first_name" value="{{ $user->first_name ?? explode(' ', $user->name, 2)[0] }}" required></div>
                                                    <div class="field"><label for="last-name-{{ $user->id }}">Nom</label><input id="last-name-{{ $user->id }}" name="last_name" value="{{ $user->last_name ?? (explode(' ', $user->name, 2)[1] ?? '') }}" required></div>
                                                    <div class="field"><label for="email-{{ $user->id }}">E-mail</label><input id="email-{{ $user->id }}" name="email" type="email" value="{{ $user->email }}" required></div>
                                                    <div class="field"><label for="account-matricule-{{ $user->id }}">Matricule</label><input id="account-matricule-{{ $user->id }}" name="matricule" value="{{ $user->matricule }}"></div>
                                                    <button class="button" type="submit">Enregistrer le compte</button>
                                                </form>
                                                <form class="admin-action-form" method="POST" action="{{ route('admin.users.role', $user) }}" data-admin-form>
                                                    @csrf @method('PUT')
                                                    <h3>Rôle</h3>
                                                    <div class="field"><label for="role-{{ $user->id }}">Rôle institutionnel</label><select id="role-{{ $user->id }}" name="role">@foreach ($roles as $role)<option value="{{ $role->slug }}" @selected($role->slug === $user->role)>{{ $role->name }}</option>@endforeach</select></div>
                                                    <div class="field"><label for="role-reason-{{ $user->id }}">Motif</label><input id="role-reason-{{ $user->id }}" name="reason" value="Rôle vérifié par l’administration." minlength="10" required></div>
                                                    <button class="button" type="submit" @disabled(auth()->user()->is($user))>Enregistrer le rôle</button>
                                                </form>
                                                <form class="admin-action-form" method="POST" action="{{ route('admin.users.status', $user) }}" data-admin-form>
                                                    @csrf @method('PATCH')
                                                    <h3>Statut du compte</h3>
                                                    <div class="field"><label for="status-{{ $user->id }}">Statut</label><select id="status-{{ $user->id }}" name="account_status">@foreach (['active' => 'Actif', 'suspended' => 'Suspendu', 'disabled' => 'Désactivé'] as $value => $label)<option value="{{ $value }}" @selected($user->account_status === $value)>{{ $label }}</option>@endforeach</select></div>
                                                    <div class="field"><label for="status-reason-{{ $user->id }}">Motif</label><input id="status-reason-{{ $user->id }}" name="reason" value="Mise à jour après vérification du compte." minlength="10" required></div>
                                                    <button class="button button-outline" type="submit" @disabled(auth()->user()->is($user))>Mettre à jour le statut</button>
                                                </form>
                                                @if ($user->student)
                                                    <form class="admin-action-form" method="POST" action="{{ route('admin.users.student-profile', $user) }}" data-admin-form>
                                                        @csrf @method('PATCH')
                                                        <h3>Profil institutionnel</h3>
                                                        <div class="field"><label for="matricule-{{ $user->id }}">Matricule étudiant</label><input id="matricule-{{ $user->id }}" name="matricule" value="{{ $user->student->matricule }}"></div>
                                                        <div class="field"><label for="filiere-{{ $user->id }}">Filière</label><select id="filiere-{{ $user->id }}" name="filiere_id" required>@foreach ($filieres as $filiere)<option value="{{ $filiere->id }}" @selected($user->student->filiere_id === $filiere->id)>{{ $filiere->name }}</option>@endforeach</select></div>
                                                        <div class="field"><label for="level-{{ $user->id }}">Niveau</label><select id="level-{{ $user->id }}" name="study_level_id" required>@foreach ($studyLevels as $level)<option value="{{ $level->id }}" @selected($user->student->study_level_id === $level->id)>{{ $level->code }} · {{ $level->name }}</option>@endforeach</select></div>
                                                        <div class="field"><label for="year-{{ $user->id }}">Année de soutenance</label><select id="year-{{ $user->id }}" name="defense_year_id"><option value="">Non renseignée</option>@foreach ($defenseYears as $year)<option value="{{ $year->id }}" @selected($user->student->defense_year_id === $year->id)>{{ $year->label }}</option>@endforeach</select></div>
                                                        <div class="field"><label for="enrollment-{{ $user->id }}">Inscription</label><select id="enrollment-{{ $user->id }}" name="enrollment_status" required>@foreach (['unknown' => 'À vérifier', 'enrolled' => 'Inscrit', 'graduated' => 'Diplômé', 'withdrawn' => 'Désinscrit'] as $value => $label)<option value="{{ $value }}" @selected($user->student->enrollment_status === $value)>{{ $label }}</option>@endforeach</select></div>
                                                        <div class="field"><label for="verified-{{ $user->id }}">Vérification institutionnelle</label><input id="verified-{{ $user->id }}" name="institutionally_verified_at" type="datetime-local" value="{{ $user->student->institutionally_verified_at?->format('Y-m-d\TH:i') }}"></div>
                                                        <div class="field"><label for="defended-{{ $user->id }}">Date de soutenance</label><input id="defended-{{ $user->id }}" name="defended_at" type="datetime-local" value="{{ $user->student->defended_at?->format('Y-m-d\TH:i') }}"></div>
                                                        <button class="button" type="submit">Enregistrer le profil</button>
                                                    </form>
                                                    @if (auth()->user()->hasPermission('deposit_authorizations.manage'))
                                                        @php
                                                            $authorization = $user->depositAuthorization;
                                                            $authorizationIsActive = $user->deposit_authorized
                                                                && $authorization?->status === 'active'
                                                                && ($authorization->expires_at === null || $authorization->expires_at->isFuture());
                                                        @endphp
                                                        <section class="admin-action-form">
                                                            <h3>Autorisation de dépôt</h3>
                                                            @if ($authorizationIsActive)
                                                                <span class="admin-status admin-status-active">Active</span>
                                                                <form method="POST" action="{{ route('admin.students.deposit-authorization.destroy', $user) }}">
                                                                    @csrf
                                                                    <div class="field"><label for="revoke-reason-{{ $user->id }}">Motif de révocation</label><input id="revoke-reason-{{ $user->id }}" name="reason" value="Révocation administrative après vérification." minlength="10" required></div>
                                                                    <button class="button button-outline" type="submit">Révoquer l’autorisation</button>
                                                                </form>
                                                            @else
                                                                <span class="admin-status admin-status-suspended">Inactive</span>
                                                                @if ($user->matricule)
                                                                    <form method="POST" action="{{ route('admin.deposit-authorizations.store') }}">
                                                                        @csrf
                                                                        <input type="hidden" name="matricule" value="{{ $user->matricule }}">
                                                                        <div class="field"><label for="grant-reason-{{ $user->id }}">Motif d’autorisation</label><input id="grant-reason-{{ $user->id }}" name="reason" value="Éligibilité vérifiée par l’administration." minlength="10" required></div>
                                                                        <button class="button" type="submit">Accorder l’autorisation</button>
                                                                    </form>
                                                                @else
                                                                    <p class="form-note">Un matricule doit être renseigné avant l’autorisation.</p>
                                                                @endif
                                                            @endif
                                                        </section>
                                                    @endif
                                                @endif
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="admin-empty">Aucun compte ne correspond à ces critères.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="admin-pagination">{{ $users->links() }}</div>
            </section>
        </div>
    </section>
@endsection

