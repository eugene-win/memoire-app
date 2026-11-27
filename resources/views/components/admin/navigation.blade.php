<nav class="admin-navigation" aria-label="Navigation d’administration">
    <a class="{{ request()->routeIs('admin.index') ? 'is-active' : '' }}" href="{{ route('admin.index') }}">Vue d’ensemble</a>
    @if (auth()->user()->hasPermission('users.manage'))
        <a class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">Comptes</a>
    @endif
    @if (auth()->user()->hasPermission('roles.manage'))
        <a class="{{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}" href="{{ route('admin.roles.index') }}">Rôles et droits</a>
    @endif
    @if (auth()->user()->hasPermission('references.manage'))
        <a class="{{ request()->routeIs('admin.references.*') ? 'is-active' : '' }}" href="{{ route('admin.references.index') }}">Référentiels</a>
    @endif
    @if (auth()->user()->hasPermission('platform_settings.manage'))
        <a class="{{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}" href="{{ route('admin.settings.index') }}">Paramètres</a>
    @endif
    @if (auth()->user()->hasPermission('activity_logs.read'))
        <a class="{{ request()->routeIs('admin.activity-logs.*') ? 'is-active' : '' }}" href="{{ route('admin.activity-logs.index') }}">Journal d’activité</a>
    @endif
</nav>
