@php
use Illuminate\Support\Facades\Auth;
@endphp

<style>
    /* Keep it simple: no hover-to-open (avoids flicker); Bootstrap handles open/close by click */
    .notification-bell .dropdown-menu {
        min-width: 320px;
        max-width: 360px;
        animation: notif-fade .12s ease-out;
    }

    @keyframes notif-fade {
        from {
            opacity: 0;
            transform: translateY(-4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="dropdown notification-bell d-inline-block">
    <button class="btn p-0 position-relative" type="button" id="notifDropdownBtn" data-bs-toggle="dropdown"
        data-bs-auto-close="outside" aria-expanded="false" aria-label="Notifications">
        <i class="fas fa-bell fa-lg"></i>

        @if($unreadCount)
            <span class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-danger"
                style="font-size:.65rem; transform: translate(35%,-35%);">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    <ul class="dropdown-menu dropdown-menu-end shadow-sm p-0" aria-labelledby="notifDropdownBtn">
        {{-- Header --}}
        <li class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between">
            <span class="fw-semibold">Notifications</span>
            @if($unreadCount > 0)
                {{-- <button class="btn btn-sm btn-link p-0" type="button" wire:click.prevent="markAllRead">
                    Mark all as read
                </button> --}}
            @endif
        </li>

        {{-- Scrollable body --}}
        <li>
            <div style="max-height: 340px; overflow:auto;" class="py-1">
                @forelse($notifications as $note)
                    <div class="px-3 py-2 border-bottom small {{ $note['read_at'] ? '' : 'bg-light-subtle' }}">
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $note['title'] }}</div>
                                @if(!empty($note['body']))
                                    <div class="text-muted">{{ $note['body'] }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <span class="text-muted">
                                {{ optional($note['created_at'])->diffForHumans() ?? 'Now' }}
                            </span>

                            {{-- <div class="d-flex gap-2">
                                @if(!empty($note['url']))
                                    <a href="{{ url($note['url']) }}" class="btn btn-sm btn-outline-primary"
                                        wire:click.stop="markAsRead('{{ $note['id'] }}')">
                                        View
                                    </a>
                                @endif

                                @if(!$note['read_at'])
                                    <button class="btn btn-sm btn-outline-secondary" type="button"
                                        wire:click.stop="markAsRead('{{ $note['id'] }}')">
                                        Mark read
                                    </button>
                                @endif
                            </div> --}}
                        </div>
                    </div>
                @empty
                    <div class="px-3 py-4 text-center text-muted">
                        No new notifications.
                    </div>
                @endforelse
            </div>
        </li>

        {{-- Footer --}}
        <li class="px-3 py-2 ">
            <div class="d-flex justify-content-center gap-3">
                @if (Auth::user()?->role === 'admin')
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-outline-primary mt-4">View all</a>
                @elseif (Auth::user()?->role === 'vendor')
                    <a href="{{ route('vendor.notifications.index') }}" class="btn btn-sm btn-outline-primary ">View all</a>
                @endif
            </div>
        </li>
    </ul>
</div>