<?php

namespace App\Livewire\Admin\Pages;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class VendorList extends Component
{
    use WithPagination;

    public string $search   = '';
    public string $approval = ''; // ''|1|0
    public string $status   = ''; // ''|1|0
    public int $perPage     = 10;

    protected $queryString = ['search', 'approval', 'status'];

    public function updatingSearch()  { $this->resetPage(); }
    public function updatingApproval(){ $this->resetPage(); }
    public function updatingStatus()  { $this->resetPage(); }

    public function toggleApproval(int $userId): void
    {
        $user = User::where('role', 'vendor')->findOrFail($userId);
        $user->is_approved = ! (bool) $user->is_approved;
        $user->save();

        session()->flash('success', 'Approval status updated.');
    }

    public function toggleActive(int $userId): void
    {
        $user = User::where('role', 'vendor')->findOrFail($userId);
        $user->is_active = ! (bool) $user->is_active;
        $user->save();

        session()->flash('success', 'Account status updated.');
    }

    public function render()
    {
        $vendors = User::query()
            ->where('role', 'vendor')
            ->when($this->search !== '', function ($q) {
                $q->where(function ($qq) {
                    $qq->where('name', 'like', '%' . $this->search . '%')
                       ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->approval !== '', function ($q) {
                // '1' => approved, '0' => not approved
                $q->where('is_approved', $this->approval === '1');
            })
            ->when($this->status !== '', function ($q) {
                // '1' => active, '0' => blocked
                $q->where('is_active', $this->status === '1');
            })
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.admin.pages.vendor-list', compact('vendors'))
            ->layout('components.layouts.admin');
    }
}
