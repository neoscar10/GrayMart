<div class="container py-4" wire:poll.5s data-start="{{ $auction->starts_at?->timestamp }}"
    data-end="{{ $auction->ends_at?->timestamp }}">

    {{-- Session success alert --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">

        <div class="col-lg-8">
            {{-- Header / summary --}}
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h5 class="mb-0">{{ $auction->product->name ?? 'Auction' }}</h5>

                        {{-- Status + countdown chip --}}
                        <div class="d-flex align-items-center gap-2">
                            @if($isLive)
                                <span class="badge bg-success">Live</span>
                                <span id="countdownChip" class="badge bg-dark-subtle text-dark">…</span>
                            @elseif($isScheduled)
                                <span class="badge bg-warning text-dark">Scheduled</span>
                                <span id="countdownChip" class="badge bg-dark-subtle text-dark">Starts in …</span>
                            @else
                                <span class="badge bg-secondary">Ended</span>
                            @endif

                            {{-- Reserve chip --}}
                            <span class="badge {{ $reserveMet ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $reserveMet ? 'Reserve met' : 'Reserve not met' }}
                            </span>
                        </div>
                    </div>

                    <div class="small text-muted mt-2">
                        Starts: {{ $auction->starts_at?->format('Y-m-d H:i') }} ·
                        Ends: {{ $auction->ends_at?->format('Y-m-d H:i') }}
                    </div>

                    {{-- Gallery (no blur/crop) --}}
                    @php
                        $img = (is_array($auction->product->images ?? null) && count($auction->product->images))
                            ? asset('storage/' . $auction->product->images[0])
                            : asset('assets/images/thumbs/product-placeholder.png');
                    @endphp
                    <div class="mt-3 text-center">
                        <img src="{{ $img }}" alt="{{ $auction->product->name ?? 'Product' }}"
                            class="img-fluid rounded border d-inline-block"
                            style="max-height:360px; width:auto; object-fit:contain; background:#f8f9fa;">
                    </div>
                </div>
            </div>

            {{-- Bids table --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">Bids</div>
                    <div class="text-muted small">
                        @if($current)
                            Current: ${{ number_format($current, 2) }}
                        @else
                            No bids yet
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:35%">Time</th>
                                    <th style="width:45%">Bidder</th>
                                    <th class="text-end" style="width:20%">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bids as $bid)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($bid->created_at)->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            @if($auction->anonymize_bidders)
                                                @php
                                                    $name = $bid->user?->name ?? 'User';
                                                    $first = strtok($name, ' ') ?: 'User';
                                                    $initial = strtoupper(substr(trim((string) strtok(' ')), 0, 1));
                                                    $hash = strtoupper(substr(md5((string) ($bid->user?->id ?? 0)), 0, 4));
                                                    $anon = $initial ? "$first $initial." : $first;
                                                @endphp
                                                {{ $anon }} • #{{ $hash }}
                                            @else
                                                {{ $bid->user?->name ?? 'User' }}
                                            @endif
                                        </td>
                                        <td class="text-end">${{ number_format($bid->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-5">No bids yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    {{ $bids->onEachSide(1)->links() }}
                </div>
            </div>
        </div>

        {{-- Right rail --}}
        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 1rem;">
                <div class="card-body">

                    {{-- Price block --}}
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="small text-muted">Next minimum</div>
                            <div class="fw-semibold">${{ number_format($nextMin, 2) }}</div>
                        </div>
                        @if(!$reserveMet && $auction->product->reserve_price)
                            <div class="small text-muted">
                                Reserve: ${{ number_format($auction->product->reserve_price, 2) }}
                            </div>
                        @endif
                    </div>

                    {{-- Bid input --}}
                    <div class="input-group mb-2">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" class="form-control" wire:model.defer="amount"
                            placeholder="{{ number_format($nextMin, 2) }}" @disabled(!$isLive)>
                    </div>
                    @error('amount') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-secondary btn-sm" wire:click="quick('1x')" @disabled(!$isLive)>+
                            min</button>
                        <button class="btn btn-secondary btn-sm" wire:click="quick('2x')" @disabled(!$isLive)>+
                            2×</button>
                    </div>

                    {{-- Primary actions --}}
                    <button class="btn btn-primary w-100 mb-2" wire:click="placeBid" wire:loading.attr="disabled"
                        @disabled(!$isLive)>
                        <span wire:loading.remove wire:target="placeBid">Place Bid</span>
                        <span wire:loading wire:target="placeBid"><i class="fa fa-spinner fa-spin me-1"></i>
                            Placing…</span>
                    </button>

                    {{-- Buy Now (only while live and price exists) --}}
                    @if($isLive && !is_null($auction->product->buy_now_price) && (float) $auction->product->buy_now_price > 0)
                        <button class="btn btn-success w-100" wire:click="confirmBuyNow">
                            Buy Now — ${{ number_format($auction->product->buy_now_price, 2) }}
                        </button>
                    @endif

                    {{-- Winner CTA after end --}}
                    @if($isEnded)
                        @if($userIsWinner)
                            <button class="btn btn-success w-100 mt-2" wire:click="payNow" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="payNow">You won — Pay now</span>
                                <span wire:loading wire:target="payNow"><i class="fa fa-spinner fa-spin me-1"></i>
                                    Adding…</span>
                            </button>
                            <div class="small text-muted mt-2">
                                Your winning bid: <strong>${{ number_format($winningAmount, 2) }}</strong>
                            </div>
                        @else
                            <div class="alert alert-light mt-2 mb-0">
                                Auction ended. Final price: <strong>${{ number_format($finalPrice ?? 0, 2) }}</strong>
                            </div>
                        @endif
                    @endif

                    <hr>
                    <div class="small text-muted">
                        Bids increase by at least the product’s minimum increment.
                        Anti-sniping may extend the auction near the end.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Buy Now Modal --}}
    <div wire:ignore.self class="modal fade" id="buyNowModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Confirm Buy Now</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Proceed to buy now for <strong>${{ number_format($auction->product->buy_now_price, 2) }}</strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" wire:click="doBuyNow" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="doBuyNow">Yes, continue</span>
                        <span wire:loading wire:target="doBuyNow"><i class="fa fa-spinner fa-spin me-1"></i>
                            Working…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const wrap = document.querySelector('[data-start][data-end]');
        if (!wrap) return;

        const start = parseInt(wrap.getAttribute('data-start') || '0', 10) * 1000;
        const end = parseInt(wrap.getAttribute('data-end') || '0', 10) * 1000;
        const chip = document.getElementById('countdownChip');

        function dur(ms) {
            const s = Math.max(0, Math.floor(ms / 1000));
            const d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60), ss = s % 60;
            const pad = n => (n < 10 ? '0' : '') + n;
            return d > 0 ? `${d}d ${pad(h)}h ${pad(m)}m ${pad(ss)}s`
                : h > 0 ? `${h}h ${pad(m)}m ${pad(ss)}s`
                    : `${m}m ${pad(ss)}s`;
        }

        function tick() {
            const now = Date.now();
            if (!chip) return;
            if (now < start) {
                chip.textContent = `Starts in ${dur(start - now)}`;
            } else if (now >= start && now < end) {
                chip.textContent = `${dur(end - now)} left`;
            } else {
                chip.textContent = `Ended`;
            }
        }
        tick(); setInterval(tick, 1000);

        // Livewire modal events
        window.addEventListener('show-buynow-modal', () => {
            new bootstrap.Modal(document.getElementById('buyNowModal')).show();
        });
        window.addEventListener('hide-buynow-modal', () => {
            const m = bootstrap.Modal.getInstance(document.getElementById('buyNowModal'));
            m && m.hide();
        });
    })();
</script>