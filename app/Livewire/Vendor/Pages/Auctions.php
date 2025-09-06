<?php

namespace App\Livewire\Vendor\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use App\Models\Auction;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Auctions extends Component
{
    use WithPagination;

    // ---- Canonical statuses (match DB) ----
    private const STATUS_SCHEDULED = 'scheduled';
    private const STATUS_LIVE      = 'live';     // <- was "running" before
    private const STATUS_ENDED     = 'ended';

    // Filters
    public ?int $product = null; // from query string (?product=ID)
    public ?string $statusFilter = null; // scheduled|live|ended|null
    public int $perPage = 10;

    // Form state
    public ?int $auctionId = null;
    public ?int $product_id = null;
    public ?string $starts_at = null; // 'YYYY-MM-DDTHH:MM'
    public ?string $ends_at = null;   // 'YYYY-MM-DDTHH:MM'
    public int $anti_sniping_window = 30; // minutes
    public bool $anonymize_bidders = false;

    // Confirmations
    public ?int $confirmDeleteId = null;
    public ?int $confirmEndId    = null;

    protected $queryString = [
        'product' => ['except' => null],
        'statusFilter' => ['except' => null],
    ];

    public function mount(): void
    {
        if ($pid = request()->query('product')) {
            $this->product = is_numeric($pid) ? (int)$pid : null;
        }
    }

    /* --------------------- CRUD --------------------- */

    public function openCreate(?int $productId = null): void
    {
        $this->resetForm();
        $this->product_id = $productId ?: $this->product;
        $this->dispatch('show-auction-modal');
    }

    public function openEdit(int $id): void
    {
        $a = Auction::with('product')
            ->where('vendor_id', auth()->id())
            ->findOrFail($id);

        Gate::authorize('update', $a);

        $this->auctionId           = $a->id;
        $this->product_id          = $a->product_id;
        $this->starts_at           = optional($a->starts_at)->format('Y-m-d\TH:i');
        $this->ends_at             = optional($a->ends_at)->format('Y-m-d\TH:i');
        $this->anti_sniping_window = (int) ($a->anti_sniping_window ?? 30);
        $this->anonymize_bidders   = (bool) $a->anonymize_bidders;

        $this->dispatch('show-auction-modal');
    }

    public function save(): void
    {
        $data = $this->validate($this->rules());

        // Ensure product belongs to vendor
        $owned = Product::where('id', $data['product_id'])
            ->where('vendor_id', auth()->id())
            ->exists();
        if (!$owned) {
            $this->addError('product_id', 'Invalid product selection.');
            return;
        }

        $start = Carbon::parse($data['starts_at']);
        $end   = Carbon::parse($data['ends_at']);

        if ($end->lte($start)) {
            $this->addError('ends_at', 'End time must be after start time.');
            return;
        }

        // Prevent overlapping scheduled/live auctions for same product
        $overlapQuery = Auction::query()
            ->where('product_id', $data['product_id'])
            ->where('vendor_id', auth()->id())
            ->when($this->auctionId, fn($q) => $q->where('id', '!=', $this->auctionId))
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($qq) use ($start, $end) {
                    // [s1,e1] overlaps [s2,e2] if s1 < e2 AND s2 < e1
                    $qq->where('starts_at', '<', $end)
                       ->where('ends_at', '>', $start);
                });
            })
            ->whereIn('status', [self::STATUS_SCHEDULED, self::STATUS_LIVE]);

        if ($overlapQuery->exists()) {
            $this->addError('starts_at', 'Overlaps an existing scheduled/live auction for this product.');
            return;
        }

        $status = $this->computeStatus($start, $end, now());

        if ($this->auctionId) {
            $a = Auction::where('vendor_id', auth()->id())->findOrFail($this->auctionId);
            Gate::authorize('update', $a);
        } else {
            $a = new Auction();
            $a->vendor_id = auth()->id();
        }

        $a->fill([
            'product_id'           => $data['product_id'],
            'starts_at'            => $start,
            'ends_at'              => $end,
            'anti_sniping_window'  => (int) $data['anti_sniping_window'],
            'anonymize_bidders'    => (bool) $data['anonymize_bidders'],
            'status'               => $status,
        ])->save();

        $this->dispatch('hide-auction-modal');
        $this->dispatch('toast', ['type'=>'success','message'=>'Auction saved.']);
        $this->resetForm();
    }

    // ---- End Now (with confirmation) ----
    public function confirmEndNow(int $id): void
    {
        $this->confirmEndId = $id;
        $this->dispatch('show-end-modal');
    }

    public function endNowConfirmed(): void
    {
        if (!$this->confirmEndId) return;

        $a = Auction::where('vendor_id', auth()->id())->findOrFail($this->confirmEndId);
        Gate::authorize('update', $a);

        // Only allow ending scheduled/live
        if (!in_array($a->status, [self::STATUS_SCHEDULED, self::STATUS_LIVE], true)) {
            $this->dispatch('toast', ['type'=>'warning','message'=>'Auction already ended.']);
        } else {
            $a->ends_at = now();
            $a->status  = self::STATUS_ENDED; // <- safe, consistent
            $a->save();
            $this->dispatch('toast', ['type'=>'success','message'=>'Auction ended.']);
        }

        $this->confirmEndId = null;
        $this->dispatch('hide-end-modal');
    }

    // ---- Delete (with confirmation) ----
    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
        $this->dispatch('show-delete-modal');
    }

    public function deleteConfirmed(): void
    {
        if (!$this->confirmDeleteId) return;

        $a = Auction::where('vendor_id', auth()->id())->findOrFail($this->confirmDeleteId);
        Gate::authorize('delete', $a);

        // Optional: block deletion of live auctions
        if ($a->status === self::STATUS_LIVE) {
            $this->dispatch('toast', ['type'=>'warning','message'=>'You cannot delete a live auction. End it first.']);
        } else {
            $a->delete();
            $this->dispatch('toast', ['type'=>'success','message'=>'Auction deleted.']);
        }

        $this->confirmDeleteId = null;
        $this->dispatch('hide-delete-modal');
    }

    /* ------------------- Helpers -------------------- */

    protected function rules(): array
    {
        return [
            'product_id'          => ['required','integer', Rule::exists('products','id')],
            'starts_at'           => ['required','date'],
            'ends_at'             => ['required','date'],
            'anti_sniping_window' => ['required','integer','min:0','max:1440'],
            'anonymize_bidders'   => ['boolean'],
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'auctionId','product_id','starts_at','ends_at',
            'anti_sniping_window','anonymize_bidders',
        ]);
        $this->anti_sniping_window = 30;
        $this->anonymize_bidders   = false;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    protected function computeStatus(Carbon $start, Carbon $end, Carbon $now): string
    {
        if ($now->lt($start)) return self::STATUS_SCHEDULED;
        if ($now->gte($start) && $now->lt($end)) return self::STATUS_LIVE;
        return self::STATUS_ENDED;
    }

    public function updatingProduct() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }

    public function render()
    {
        $vendorId = auth()->id();
        $now = now();

        // Use strict boundaries so an auction ending exactly at 'ends_at' is considered ended.
        $case = "CASE 
                    WHEN ? < starts_at THEN 'scheduled'
                    WHEN ? >= starts_at AND ? < ends_at THEN 'live'
                    ELSE 'ended'
                 END";

        $query = Auction::query()
            ->with(['product:id,name'])
            ->where('vendor_id', $vendorId)
            ->when($this->product, fn($q)=>$q->where('product_id', $this->product))
            ->select('auctions.*')
            ->selectRaw("($case) as derived_status", [$now, $now, $now])
            ->selectRaw("(SELECT MAX(bids.amount) FROM bids WHERE bids.auction_id = auctions.id) as highest_amount")
            ->orderByDesc('starts_at');

        if ($this->statusFilter) {
            $query->having('derived_status', '=', $this->statusFilter);
        }

        $auctions = $query->paginate($this->perPage);

        // Vendor’s selectable products (approved + active + reserved)
        $products = Product::query()
            ->where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->where('is_reserved', true)
            ->where('status', 'approved')
            ->orderBy('name')
            ->get(['id','name']);

        return view('livewire.vendor.pages.auctions', [
            'auctions' => $auctions,
            'products' => $products,
        ])->layout('components.layouts.vendor');
    }
}
