<?php

namespace App\Livewire\Front\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Auction;
use App\Models\Bid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

// Cart + navbar events (same pattern as your ShopPage)
use App\Support\CartManagement;
use App\Livewire\FunctionalPartials\MainNavbar;

class AuctionShow extends Component
{
    use WithPagination;

    public Auction $auction;

    // Form
    public string $amount = '';

    // UI
    public int $perPage = 10;

    // Derived status flags
    public bool $isLive = false;
    public bool $isScheduled = false;
    public bool $isEnded = false;

    // Winner state (after end)
    public bool $userIsWinner = false;
    public ?float $winningAmount = null;
    public ?float $finalPrice = null;

    public function mount(Auction $auction): void
    {
        $this->auction = $auction->load([
            'product:id,name,images,reserve_price,min_increment,buy_now_price,vendor_id',
            'vendor:id,name',
        ]);
    }

    /* ===================== Actions ===================== */

    public function quick(string $kind): void
    {
        [, $nextMin] = $this->currentAndNextMin();
        $inc = max(0.01, (float) ($this->auction->product->min_increment ?? 0.01));
        $val = match ($kind) {
            '1x'    => $nextMin,
            '2x'    => $nextMin + $inc,
            default => $nextMin
        };
        $this->amount = number_format($val, 2, '.', '');
    }

    public function placeBid(): void
    {
        if (!Auth::check()) { $this->addError('amount', 'Please sign in to place a bid.'); return; }
        if ((int) $this->auction->vendor_id === (int) Auth::id()) {
            $this->addError('amount', 'You cannot bid on your own auction.'); return;
        }

        $data = $this->validate(['amount' => ['required','numeric','min:0.01']]);

        try {
            DB::transaction(function () use ($data) {
                $a = Auction::whereKey($this->auction->id)->lockForUpdate()->firstOrFail();

                $now = now(); $start = Carbon::parse($a->starts_at); $end = Carbon::parse($a->ends_at);
                if (!($now->gte($start) && $now->lt($end))) {
                    throw ValidationException::withMessages(['amount' => 'Auction is not live.']);
                }

                if (!$a->relationLoaded('product')) {
                    $a->load('product:id,reserve_price,min_increment,buy_now_price,name,images');
                }

                $minInc  = max(0.01, (float) ($a->product->min_increment ?? 0.01));
                $highest = Bid::where('auction_id', $a->id)->max('amount');
                $nextMin = $highest ? ($highest + $minInc) : $minInc;

                $bidAmount = (float) $data['amount'];
                if ($bidAmount < $nextMin) {
                    throw ValidationException::withMessages(['amount' => 'Minimum acceptable bid is ' . number_format($nextMin, 2)]);
                }

                Bid::create([
                    'auction_id' => $a->id,
                    'user_id'    => Auth::id(),
                    'amount'     => $bidAmount,
                ]);

                // Anti-sniping
                $windowMin = (int) ($a->anti_sniping_window ?? 0);
                if ($windowMin > 0) {
                    $threshold = Carbon::parse($a->ends_at)->subMinutes($windowMin);
                    if ($now->gte($threshold) && $now->lt($a->ends_at)) {
                        $a->ends_at = Carbon::parse($a->ends_at)->addMinutes($windowMin);
                        $a->save();
                    }
                }

                $this->auction->refresh();
            });

            $this->reset('amount');
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Bid placed.']);
            session()->flash('success', 'Bid placed successfully.');
            $this->resetPage();

        } catch (ValidationException $e) {
            $this->addError('amount', $e->errors()['amount'][0] ?? $e->getMessage());
        }
    }

    public function confirmBuyNow(): void
    {
        if (!$this->buyNowAvailable()) { $this->dispatch('toast', ['type'=>'warning','message'=>'Buy Now not available.']); return; }
        if (!Auth::check())           { $this->dispatch('toast', ['type'=>'warning','message'=>'Please sign in to continue.']); return; }
        $this->dispatch('show-buynow-modal');
    }

    public function doBuyNow(): mixed
    {
        if (!$this->buyNowAvailable()) return null;
        if (!Auth::check()) return null;

        // Optionally end the auction immediately when Buy Now is used
        DB::transaction(function () {
            $a = Auction::whereKey($this->auction->id)->lockForUpdate()->firstOrFail();
            $now = now();
            if (!$now->between($a->starts_at, $a->ends_at)) {
                throw ValidationException::withMessages(['buy' => 'Auction is not live.']);
            }
            $a->ends_at = $now;
            $a->status  = 'ended';
            $a->save();
        });

        // Add to cart using auction-aware API and redirect to cart
        CartManagement::addAuctionBuyNow($this->auction->id, $this->auction->product->id);

        $this->dispatch('cart-updated');
        $this->dispatch('update-cart-count', total_count: \App\Support\CartManagement::count())->to(MainNavbar::class);
        $this->dispatch('hide-buynow-modal');
        session()->flash('success', 'Added to cart. Proceed to checkout.');

        return redirect()->to('/cart');
    }

    public function payNow(): mixed
    {
        if (!$this->isEnded) return null;
        if (!Auth::check()) { $this->dispatch('toast', ['type'=>'warning','message'=>'Please sign in first.']); return null; }

        $top = Bid::where('auction_id', $this->auction->id)
            ->orderByDesc('amount')->orderBy('id','desc')->first();

        if (!$top || (int)$top->user_id !== (int)Auth::id()) {
            $this->dispatch('toast', ['type'=>'warning','message'=>'Only the winner can pay now.']); return null;
        }

        // Add to cart at the winning amount (qty fixed to 1) and redirect to cart
        CartManagement::addAuctionWinner($this->auction->id, $this->auction->product->id, (float) $top->amount);

        $this->dispatch('cart-updated');
        $this->dispatch('update-cart-count', total_count: \App\Support\CartManagement::count())->to(MainNavbar::class);
        session()->flash('success', 'Added to cart. Proceed to checkout.');

        return redirect()->to('/cart');
    }

    /* ===================== Computed helpers ===================== */

    public function currentAndNextMin(): array
    {
        $minInc  = max(0.01, (float) ($this->auction->product->min_increment ?? 0.01));
        $current = Bid::where('auction_id', $this->auction->id)->max('amount');
        $nextMin = $current ? ($current + $minInc) : $minInc;
        return [(float) $current, (float) $nextMin];
    }

    public function reserveMet(?float $current): bool
    {
        $reserve = $this->auction->product->reserve_price;
        return $reserve ? ((float)$current >= (float)$reserve) : true;
    }

    public function buyNowAvailable(): bool
    {
        $p = $this->auction->product;
        return !is_null($p->buy_now_price)
            && (float) $p->buy_now_price > 0
            && now()->between($this->auction->starts_at, $this->auction->ends_at);
    }

    public function render()
    {
        $bids = Bid::with('user:id,name')
            ->where('auction_id', $this->auction->id)
            ->orderByDesc('id')
            ->paginate($this->perPage);

        $now = now(); $start = Carbon::parse($this->auction->starts_at); $end = Carbon::parse($this->auction->ends_at);
        $this->isLive      = $now->between($start, $end);
        $this->isScheduled = $now->lt($start);
        $this->isEnded     = $now->gte($end);

        $current = (float) ($bids->getCollection()->max('amount') ?? 0);
        [, $nextMin] = $this->currentAndNextMin();
        $reserveMet = $this->reserveMet($current);

        $this->userIsWinner = false; $this->winningAmount = null; $this->finalPrice = null;
        if ($this->isEnded) {
            $top = Bid::where('auction_id', $this->auction->id)->orderByDesc('amount')->orderBy('id','desc')->first();
            if ($top) {
                $this->finalPrice = (float) $top->amount;
                if (Auth::check() && (int)$top->user_id === (int)Auth::id()) {
                    $this->userIsWinner = true;
                    $this->winningAmount = (float) $top->amount;
                }
            }
        }

        return view('livewire.front.pages.auction-show', [
            'bids'         => $bids,
            'current'      => $current ?: null,
            'nextMin'      => $nextMin,
            'reserveMet'   => $reserveMet,
            'isLive'       => $this->isLive,
            'isScheduled'  => $this->isScheduled,
            'isEnded'      => $this->isEnded,
            'userIsWinner' => $this->userIsWinner,
            'winningAmount'=> $this->winningAmount,
            'finalPrice'   => $this->finalPrice,
        ]);
    }
}
