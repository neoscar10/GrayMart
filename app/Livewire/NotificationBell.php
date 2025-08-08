<?php
// app/Livewire/NotificationBell.php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationBell extends Component
{
    public $notifications = [];
    public $unreadCount   = 0;

    /**
     * Combine the “mark all read” event with your Pusher listener.
     */
    public function getListeners(): array
    {
        $userId = Auth::id();

        return [
            // Custom Livewire event when page marks all read
            'notificationsRead' => 'handleNotificationsRead',

            // Real‑time Pusher broadcast listener
            "echo:App.Models.User.{$userId},Illuminate\\Notifications\\Events\\BroadcastNotificationCreated"
                => 'notifyReceived',
        ];
    }

    public function mount()
    {
        $user = Auth::user();

        $this->notifications = $user
            ->unreadNotifications
            ->take(5)
            ->map(fn($n) => $n->data)
            ->toArray();

        $this->unreadCount = count($this->notifications);
    }

    public function notifyReceived($event)
    {
        $this->unreadCount++;
        array_unshift($this->notifications, $event['data']);
        $this->notifications = array_slice($this->notifications, 0, 5);
    }

    public function handleNotificationsRead()
    {
        $this->unreadCount   = 0;
        $this->notifications = [];
    }

    public function markAsRead($index)
    {
        $user = Auth::user();
        $notification = $user->unreadNotifications->get($index);

        if ($notification) {
            $notification->markAsRead();
            $this->unreadCount--;
            unset($this->notifications[$index]);
            $this->notifications = array_values($this->notifications);
        }
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
