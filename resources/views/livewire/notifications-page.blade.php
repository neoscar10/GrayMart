{{-- resources/views/livewire/notifications-page.blade.php --}}
<div class="pt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>All Notifications</h4>
        <button class="btn btn-outline-secondary" wire:click="markAllRead">
            Mark All Read
        </button>
    </div>

    <ul class="list-group">
        @foreach($notifications as $note)
            <li class="list-group-item {{ $note->read_at ? '' : 'bg-light' }}">
                <a href="{{ $note->data['url'] ?? '#' }}">
                    <strong>{{ $note->data['title'] }}</strong><br>
                    <small>{{ $note->data['body'] }}</small>
                </a>
                <span class="text-muted float-end">
                    {{ $note->created_at->diffForHumans() }}
                </span>
            </li>
        @endforeach
    </ul>

    {{ $notifications->links() }}
</div>