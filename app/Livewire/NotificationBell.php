<?php
// app/Livewire/NotificationBell.php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationBell extends Component
{
    public array $notifications = []; // each: ['id','title','body','url','read_at','created_at']
    public int $unreadCount = 0;
    public int $limit = 7;

    public function getListeners(): array
    {
        $userId = Auth::id();

        return [
            // When some other component marks as read, refresh this bell
            'notificationsRead' => 'refreshBell',

            // Real-time broadcast from Laravel notifications (private channel)
            "echo-private:App.Models.User.{$userId},Illuminate\\Notifications\\Events\\BroadcastNotificationCreated"
                => 'notifyReceived',
        ];
    }

    public function mount(): void
    {
        $this->refreshBell();
    }

    public function refreshBell(): void
    {
        $user = Auth::user();
        if (!$user) {
            $this->notifications = [];
            $this->unreadCount = 0;
            return;
        }

        // Total unread for the badge
        $this->unreadCount = (int) $user->unreadNotifications()->count();

        // Latest notifications (read + unread) for the dropdown list
        $this->notifications = $user->notifications()
            ->latest()
            ->limit($this->limit)
            ->get()
            ->map(function ($n) {
                $data = (array) $n->data;
                return [
                    'id'         => (string) $n->id,
                    'title'      => $data['title'] ?? 'Notification',
                    'body'       => $data['body']  ?? '',
                    'url'        => $data['url']   ?? null,
                    'read_at'    => $n->read_at,              // Carbon|null
                    'created_at' => $n->created_at,           // Carbon
                ];
            })->toArray();
    }

    public function notifyReceived(array $event): void
    {
        // Increase unread badge
        $this->unreadCount++;

        // Build a new item from the broadcast payload
        $data = $event['data'] ?? [];
        $this->notifications = array_merge(
            [[
                'id'         => (string) ($event['id'] ?? ''),
                'title'      => $data['title'] ?? 'Notification',
                'body'       => $data['body']  ?? '',
                'url'        => $data['url']   ?? null,
                'read_at'    => null,
                'created_at' => now(),
            ]],
            $this->notifications
        );

        // Trim to limit
        $this->notifications = array_slice($this->notifications, 0, $this->limit);
    }

    public function markAsRead(string $notificationId): void
    {
        $user = Auth::user();
        if (!$user) return;

        $notif = $user->notifications()->where('id', $notificationId)->first();
        if ($notif && !$notif->read_at) {
            $notif->markAsRead();
            // Update badge
            $this->unreadCount = max(0, $this->unreadCount - 1);
        }

        // Update the local list (flip read_at on the matched item)
        foreach ($this->notifications as &$n) {
            if ($n['id'] === $notificationId) {
                $n['read_at'] = now();
                break;
            }
        }
        unset($n);
    }

    public function markAllRead(): void
    {
        $user = Auth::user();
        if (!$user) return;

        $user->unreadNotifications->markAsRead();
        $this->unreadCount = 0;

        // Flip local list to read
        foreach ($this->notifications as &$n) {
            $n['read_at'] = $n['read_at'] ?: now();
        }
        unset($n);

        // Let other components refresh if needed
        $this->dispatch('notificationsRead');
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
