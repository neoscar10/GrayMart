<?php


namespace App\Livewire\Admin\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Review;
use App\Models\ReviewReport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ReviewModeration extends Component
{
    use WithPagination;

    public $search           = '';
    public $status           = '';
    public $reportedOnly     = false;

    public $selectedReview;
    public $rejectionReason  = '';

    // holds the collection of report messages for the selected review
    public $reportMessages   = [];

    protected $queryString = ['search','status','reportedOnly'];

    protected $rules = [
        'rejectionReason' => 'required_if:status,rejected|string|min:5',
    ];

    public function updating($field)
    {
        if (in_array($field, ['search','status','reportedOnly'])) {
            $this->resetPage();
        }
    }

    // — Approve / Reject / Visibility —

    public function approveReview($id)
    {
        Review::findOrFail($id)
            ->update(['status'=>'approved']);
        Log::info("Review {$id} approved by admin ".Auth::id());
        session()->flash('success','Review approved.');
    }

    public function toggleVisibility($id)
    {
        $r = Review::findOrFail($id);
        $r->update(['visible'=>! $r->visible]);
    }

    public function openRejectModal($id)
    {
        $this->selectedReview   = Review::findOrFail($id);
        $this->rejectionReason  = '';
        $this->dispatch('showRejectModal');
    }

    public function rejectConfirmed()
    {
        $this->validateOnly('rejectionReason');
        $this->selectedReview->update([
            'status'           => 'rejected',
            'rejection_reason' => $this->rejectionReason,
        ]);
        Log::info("Review {$this->selectedReview->id} rejected by admin ".Auth::id());
        $this->dispatch('hideRejectModal');
        session()->flash('success','Review rejected.');
        $this->reset('selectedReview','rejectionReason');
    }

    // — Report Messages —

    public function openReportsModal($reviewId)
    {
        $this->selectedReview  = Review::with('reports.reporter')->findOrFail($reviewId);
        $this->reportMessages  = $this->selectedReview->reports;
        $this->dispatch('showReportsModal');
    }

    public function render()
    {
        $query = Review::with('user','rateable')
            ->when($this->search,       fn($q)=> $q->where('comment','like',"%{$this->search}%"))
            ->when($this->status,       fn($q)=> $q->where('status',$this->status))
            ->when($this->reportedOnly, fn($q)=> $q->where('reported',true))
            ->orderByDesc('created_at');

        $reviews = $query->paginate(10);

        return view('livewire.admin.pages.review-moderation', compact('reviews'))
                    ->layout('components.layouts.admin');
    }
}
