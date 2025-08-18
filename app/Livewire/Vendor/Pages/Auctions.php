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

    // Filters
    public ?int $product = null; // from query string (?product=ID)
    public ?string $statusFilter = null; // scheduled|running|ended|null
    public int $perPage = 10;

    // Form state
    public ?int $auctionId = null;
    public ?int $product_id = null;
    public ?string $starts_at = null; // 'YYYY-MM-DDTHH:MM'
    public ?string $ends_at = null;   // 'YYYY-MM-DDTHH:MM'
    public int $anti_sniping_window = 30; // minutes
    public bool $anonymize_bidders = false;

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

    public function endNow(int $id): void
    {
        $a = Auction::where('vendor_id', auth()->id())->findOrFail($id);
        Gate::authorize('update', $a);
        $a->ends_at = now();
        $a->status  = 'ended';
        $a->save();
        $this->dispatch('toast', ['type'=>'success','message'=>'Auction ended.']);
    }

    public function delete(int $id): void
    {
        $a = Auction::where('vendor_id', auth()->id())->findOrFail($id);
        Gate::authorize('delete', $a);
        $a->delete();
        $this->dispatch('toast', ['type'=>'success','message'=>'Auction deleted.']);
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
            'anti_sniping_window','anonymize_bidders'
        ]);
        $this->anti_sniping_window = 30;
        $this->anonymize_bidders   = false;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    protected function computeStatus(Carbon $start, Carbon $end, Carbon $now): string
    {
        if ($now->lt($start)) return 'scheduled';
        if ($now->between($start, $end)) return 'running';
        return 'ended';
    }

    public function updatingProduct() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }

    public function render()
    {
        $vendorId = auth()->id();
        $now = now();

        // CASE expression to derive status at query time
        $case = "CASE 
                    WHEN ? < starts_at THEN 'scheduled'
                    WHEN ? BETWEEN starts_at AND ends_at THEN 'running'
                    ELSE 'ended'
                 END";

        $query = Auction::query()
            ->with(['product:id,name'])
            ->where('vendor_id', $vendorId)
            ->when($this->product, fn($q)=>$q->where('product_id', $this->product))
            // Highest bid as a scalar subquery
            ->select('auctions.*')
            ->selectRaw("($case) as derived_status", [$now, $now])
            ->selectRaw("(SELECT MAX(bids.amount) FROM bids WHERE bids.auction_id = auctions.id) as highest_amount")
            ->orderByDesc('starts_at');

        // Filter by derived status using HAVING (allows alias)
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
