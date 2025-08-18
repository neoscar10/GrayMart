<?php
// app/Livewire/NotificationsPage.php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class NotificationsPage extends Component
{
    use WithPagination;

    public function mount()
    {
        
        $this->markAllRead();
    }


    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->resetPage();

        // fire a Livewire event
        $this->dispatch('notificationsRead');
    }

    public function render()
    {
        $notifications = Auth::user()
                             ->notifications()
                             ->paginate(15);

        if (Auth::user()->role == 'admin'){
            return view('livewire.notifications-page', compact('notifications'))
                ->layout('components.layouts.admin');
                }
        else if(Auth::user()->role == 'vendor'){
            return view('livewire.notifications-page', compact('notifications'))
                ->layout('components.layouts.vendor');
        }
    }
}
