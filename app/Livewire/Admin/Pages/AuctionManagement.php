<?php

namespace App\Livewire\Admin\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Auction;
use App\Models\Certificate;

class AuctionManagement extends Component
{
    use WithPagination;

    public $tab = 'certificates'; // 'certificates','upcoming','live','closed'
    public $search = '';

    public $selectedCertificate;
    public $rejectionReason;
    public $selectedAuction;

    protected $queryString = ['tab', 'search'];

    public function setTab($tab)
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // ——— Certificate actions ———

    // Open the approve‑confirmation modal
    public function openApproveCertificateModal($id)
    {
        $this->selectedCertificate = Certificate::with('product.vendor')->findOrFail($id);
        $this->dispatch('showApproveCertModal');
    }

    // Actually approve
    public function approveCertificateConfirmed()
    {
        $this->selectedCertificate->update(['status' => 'approved']);
        $this->dispatch('hideApproveCertModal');
        session()->flash('success', 'Certificate approved.');
        $this->selectedCertificate = null;
    }

    // Open reject modal (also hide approve modal if needed)
    public function openRejectCertificateModal($id)
    {
        $this->selectedCertificate = Certificate::findOrFail($id);
        $this->dispatch('hideApproveCertModal');
        $this->dispatch('showRejectCertModal');
    }

    public function rejectCertificateConfirmed()
    {
        $this->validate(['rejectionReason' => 'required|string|min:5']);

        $this->selectedCertificate->update([
            'status'           => 'rejected',
            'rejection_reason' => $this->rejectionReason,
        ]);

        $this->dispatch('hideRejectCertModal');
        session()->flash('success', 'Certificate rejected.');
        $this->reset(['rejectionReason', 'selectedCertificate']);
    }

    // ——— Auction actions ———

    public function forceStartAuction($id)
    {
        Auction::findOrFail($id)->update(['status' => 'live']);
        session()->flash('success', 'Auction started.');
    }

    public function forceCloseAuction($id)
    {
        Auction::findOrFail($id)->update(['status' => 'closed']);
        session()->flash('success', 'Auction closed.');
    }

    public function openBidsModal($id)
    {
        $this->selectedAuction = Auction::with('bids.user')->findOrFail($id);
        $this->dispatch('showBidsModal');
    }

    public function render()
    {
        if ($this->tab === 'certificates') {
            $query = Certificate::with('product.vendor');
        } else {
            $statusMap = [
                'upcoming' => 'scheduled',
                'live'     => 'live',
                'closed'   => 'closed',
            ];
            $query = Auction::with('product.vendor')
                ->where('status', $statusMap[$this->tab]);
        }

        if ($this->search) {
            $query->whereHas('product', fn($q) =>
                $q->where('name', 'like', "%{$this->search}%")
            );
        }

        $items = $query->latest()->paginate(10);

        return view('livewire.admin.pages.auction-management', compact('items'))
            ->layout('components.layouts.admin');
    }
}
