<article class="dashboard-card dashboard-notifications" aria-labelledby="notifications-title">
    <div class="dashboard-card-heading">
        <span class="benefit-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>
            </svg>
        </span>
        <div>
            <h2 id="notifications-title">Notifications</h2>
            <p>{{ $unreadNotificationsCount }} non lue{{ $unreadNotificationsCount === 1 ? '' : 's' }}</p>
        </div>
    </div>

    @if ($notifications->isEmpty())
        <div class="dashboard-empty dashboard-empty-compact">
            <strong>Aucune notification pour le moment</strong>
            <p>Les mises à jour de vos dépôts et de vos accès apparaîtront ici.</p>
        </div>
    @else
        <ul class="notification-list">
            @foreach ($notifications as $notification)
                <li class="notification-item {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                    <div class="notification-content">
                        <strong>{{ $notification->data['title'] ?? 'Mise à jour' }}</strong>
                        <p>{{ $notification->data['message'] ?? '' }}</p>
                        <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->format('d/m/Y H:i') }}</time>
                    </div>
                    @unless ($notification->read_at)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            <button class="text-button" type="submit">Marquer comme lue</button>
                        </form>
                    @endunless
                </li>
            @endforeach
        </ul>
    @endif
</article>
