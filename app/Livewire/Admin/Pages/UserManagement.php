<?php

namespace App\Livewire\Admin\Pages;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $role = '';
    public $status = '';
    public $perPage = 10;

    public $selectedUserId;
    public $editName;
    public $editRole;

    protected $queryString = ['search', 'role', 'status'];

    protected $rules = [
        'editName' => 'required|string|min:3',
        'editRole' => 'required|in:admin,vendor,customer',
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingRole() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }

    public function openEditModal($userId)
    {
        $user = User::findOrFail($userId);
        $this->selectedUserId = $user->id;
        $this->editName = $user->name;
        $this->editRole = $user->role;
        $this->dispatch('showEditModal');
    }

    public function updateUser()
    {
        $this->validate();
        User::where('id', $this->selectedUserId)
            ->update(['name' => $this->editName, 'role' => $this->editRole]);
        $this->dispatch('hideEditModal');
        $this->reset(['selectedUserId', 'editName', 'editRole']);
        session()->flash('success', 'User updated successfully.');
    }

    public function toggleApproval($userId)
    {
        $user = User::findOrFail($userId);
        $user->is_approved = !$user->is_approved;
        $user->save();
    }

    public function toggleActive($userId)
    {
        $user = User::findOrFail($userId);
        $user->is_active = !$user->is_active;
        $user->save();
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%"))
            ->when($this->role, fn($q) => $q->where('role', $this->role))
            ->when($this->status !== '', fn($q) => $q->where('is_active', $this->status))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.pages.user-management', compact('users'))
            ->layout('components.layouts.admin');
    }
}
