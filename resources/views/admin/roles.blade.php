@extends('layouts.library')

@section('title', 'Rôles et permissions · Administration ENEAM')

@section('content')
    <section class="admin-page">
        <div class="container">
            @include('components.admin.navigation')
            <header class="admin-welcome">
                <div><span class="section-kicker">Administration ENEAM</span><h1>Rôles et permissions</h1><p>Attribuez uniquement les habilitations nécessaires à chaque profil.</p></div>
                <span class="account-badge">{{ $roles->count() }} rôle(s)</span>
            </header>
            <div class="admin-feedback" role="status" aria-live="polite" hidden></div>
            <div class="admin-role-grid">
                @foreach ($roles as $role)
                    <section class="admin-panel">
                        <div class="admin-panel-heading"><div><h2>{{ $role->name }}</h2><p>{{ $role->description }}</p></div><span class="admin-count">{{ $role->users()->count() }} compte(s)</span></div>
                        <form class="admin-action-form admin-permission-form" method="POST" action="{{ route('admin.roles.permissions', $role) }}" data-admin-form data-admin-json>
                            @csrf @method('PUT')
                            @foreach ($permissions as $permission)
                                <label class="admin-permission">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->slug }}" @checked($role->permissions->contains('id', $permission->id))>
                                    <span><strong>{{ $permission->name }}</strong><small>{{ $permission->slug }}</small></span>
                                </label>
                            @endforeach
                            <div class="field"><label for="permissions-reason-{{ $role->id }}">Motif de la modification</label><input id="permissions-reason-{{ $role->id }}" name="reason" value="Révision des habilitations du rôle." minlength="10" required></div>
                            <button class="button" type="submit">Enregistrer les permissions</button>
                        </form>
                    </section>
                @endforeach
            </div>
        </div>
    </section>
@endsection

