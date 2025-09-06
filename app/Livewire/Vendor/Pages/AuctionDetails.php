<?php

namespace App\Livewire\Vendor\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Gate;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Carbon\Carbon;

#[Layout('components.layouts.vendor')]
class AuctionDetails extends Component
{
    use WithPagination;

    /** Canonical statuses (MUST match DB values) */
    private const STATUS_SCHEDULED = 'scheduled';
    private const STATUS_LIVE      = 'live';
    private const STATUS_ENDED     = 'ended';

    /** Route-bound model props */
    public int $auctionId;
    public Auction $auction;

    /** UI state */
    public int $perPage = 20;
    public ?int $confirmEndId = null;
    public ?int $confirmExtendId = null;
    public int $extendMinutes = 5; // default extend value

    /**
     * Implicitly receives the Auction model via route-model binding:
     * Route: /vendor/auction-details/{auction}
     */
    public function mount(Auction $auction): void
    {
        // Ownership + policy
        abort_unless($auction->vendor_id === auth()->id(), 403);
        Gate::authorize('view', $auction);

        $this->auctionId = $auction->id;
        $this->auction   = $auction->load(['product:id,name', 'vendor:id,name']);
    }

    /* ========================= Controls ========================= */

    public function confirmEndNow(): void
    {
        $this->confirmEndId = $this->auctionId;
        $this->dispatch('show-end-modal');
    }

    public function endNowConfirmed(): void
    {
        if (!$this->confirmEndId) return;

        $a = Auction::where('vendor_id', auth()->id())->findOrFail($this->confirmEndId);
        Gate::authorize('update', $a);

        if ($this->derivedStatus($a) === self::STATUS_ENDED) {
            $this->dispatch('toast', ['type' => 'info', 'message' => 'Auction already ended.']);
        } else {
            $a->ends_at = now();
            $a->status  = self::STATUS_ENDED;
            $a->save();

            $this->dispatch('toast', ['type' => 'success', 'message' => 'Auction ended now.']);
        }

        $this->confirmEndId = null;
        $this->dispatch('hide-end-modal');
        $this->refreshAuction();
    }

    public function confirmExtend(): void
    {
        $this->confirmExtendId = $this->auctionId;
        $this->dispatch('show-extend-modal');
    }

    public function extendConfirmed(): void
    {
        if (!$this->confirmExtendId) return;

        $a = Auction::where('vendor_id', auth()->id())->findOrFail($this->confirmExtendId);
        Gate::authorize('update', $a);

        $status = $this->derivedStatus($a);
        if (!in_array($status, [self::STATUS_SCHEDULED, self::STATUS_LIVE], true)) {
            $this->dispatch('toast', ['type' => 'warning', 'message' => 'Only scheduled or live auctions can be extended.']);
        } else {
            $mins = max(1, (int) $this->extendMinutes);
            $a->ends_at = Carbon::parse($a->ends_at)->addMinutes($mins);
            $a->save();

            $this->dispatch('toast', ['type' => 'success', 'message' => "Extended by {$mins} minute(s)."]);
        }

        $this->confirmExtendId = null;
        $this->dispatch('hide-extend-modal');
        $this->refreshAuction();
    }

    /** Refresh auction state (also triggered by wire:poll) */
    public function refreshAuction(): void
    {
        $this->auction->refresh();
        $this->resetPage(); // keep newest bids visible
    }

    /* ========================= Helpers ========================= */

    public function derivedStatus(?Auction $a = null): string
    {
        $a = $a ?: $this->auction;

        $now   = now();
        $start = Carbon::parse($a->starts_at);
        $end   = Carbon::parse($a->ends_at);

        if ($now->lt($start)) return self::STATUS_SCHEDULED;
        if ($now->gte($start) && $now->lt($end)) return self::STATUS_LIVE;
        return self::STATUS_ENDED;
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_LIVE      => 'bg-success',
            self::STATUS_SCHEDULED => 'bg-warning text-dark',
            default                => 'bg-secondary',
        };
    }

    public function anonymize(?User $user): string
    {
        if (!$user) return 'Bidder';
        $idPart  = substr(strtoupper(md5((string) $user->id)), 0, 4);
        $name    = $user->name ?? 'User';
        $first   = strtok($name, ' ') ?: 'User';
        $initial = trim((string) strtok(' '));
        $initial = $initial ? strtoupper(substr($initial, 0, 1)) . '.' : '';
        return "{$first} {$initial} • #{$idPart}";
    }

    /* ========================= Render ========================= */

    public function render()
    {
        $bids = Bid::with('user:id,name')
            ->where('auction_id', $this->auctionId)
            ->orderByDesc('id')
            ->paginate($this->perPage);

        $highest = $bids->getCollection()->max('amount');

        // The #[Layout(...)] attribute applies the layout; return the plain view here.
        return view('livewire.vendor.pages.auction-details', [
            'bids'     => $bids,
            'highest'  => $highest,
            'derived'  => $this->derivedStatus(),
        ]);
    }
}
