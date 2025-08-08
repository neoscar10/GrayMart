<style>
    .notification-bell .dropdown-menu {
  display: none;          /* hide by default */
  margin-top: 0.5rem;     /* slight space below bell */
}

.notification-bell:hover .dropdown-menu {
  display: block;         /* show on hover */
}

/* Optional: smooth fade-in */
.notification-bell .dropdown-menu {
  transition: opacity 0.15s ease-in-out;
  opacity: 0;
}
.notification-bell:hover .dropdown-menu {
  opacity: 1;
}

</style>
<div class="notification-bell position-relative d-inline-block">
    <button class="btn p-0" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-bell fa-lg"></i>

        @if($unreadCount)
            <span class="position-absolute top-0 end-2 badge rounded-pill bg-danger p-1">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        @forelse($notifications as $i => $note)
            <li class="px-2 py-1">
                <a href="{{ $note['url'] ?? '#' }}" class="d-flex justify-content-between align-items-start"
                    wire:click="markAsRead({{ $i }})">
                    <div>
                        <strong>{{ $note['title'] }}</strong><br>
                        <small>{{ $note['body'] }}</small>
                    </div>
                    <small class="text-muted">Now</small>
                </a>
            </li>
        @empty
            <li class="px-3 py-2 text-center text-muted">No new notifications</li>
        @endforelse

        <li>
            <hr class="dropdown-divider">
        </li>
        <li class="text-center">
            <a href="{{ route('admin.notifications.index') }}">View all</a>
        </li>
    </ul>
</div>