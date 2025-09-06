<div class="container-fluid py-4" wire:poll.5s="refreshAuction">

    {{-- Header / Breadcrumb-ish --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h4 class="mb-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-gavel"></i>
                Auction Detail
                <span class="badge {{ $this->statusBadgeClass($derived) }}">{{ ucfirst($derived) }}</span>
            </h4>
            <div class="text-muted small">
                Product: <strong>{{ $auction->product->name ?? '—' }}</strong>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('vendor.auctions.index', ['product' => $auction->product_id]) }}"
                class="btn btn-outline-secondary">
                <i class="fa-solid fa-arrow-left-long me-1"></i> Back
            </a>

            @if($derived !== 'ended')
                <button class="btn btn-outline-warning" wire:click="confirmEndNow">
                    <i class="fa-solid fa-stopwatch me-1"></i> End Now
                </button>
                <button class="btn btn-primary" wire:click="confirmExtend">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i> Extend
                </button>
            @endif
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Starts</div>
                    <div class="fw-semibold">{{ \Carbon\Carbon::parse($auction->starts_at)->format('Y-m-d H:i') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small d-flex align-items-center gap-1">
                        Ends
                        @if($derived === 'live')
                            <span class="badge bg-success">Live</span>
                        @endif
                    </div>
                    <div class="fw-semibold">{{ \Carbon\Carbon::parse($auction->ends_at)->format('Y-m-d H:i') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Countdown</div>
                    <div id="countdown" class="fw-semibold"
                        data-start="{{ \Carbon\Carbon::parse($auction->starts_at)->timestamp }}"
                        data-end="{{ \Carbon\Carbon::parse($auction->ends_at)->timestamp }}"
                        data-now="{{ now()->timestamp }}">
                        —
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Highest Bid</div>
                    <div class="fw-semibold">
                        @if(!is_null($highest))
                            ₦{{ number_format($highest, 2) }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bids table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="fw-semibold">
                Bids
                @if($auction->anonymize_bidders)
                    <span class="badge bg-info text-dark ms-2">Anonymized</span>
                @endif
            </div>
            <div class="text-muted small">
                Showing {{ $bids->firstItem() }}–{{ $bids->lastItem() }} of {{ $bids->total() }}
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:25%">Time</th>
                            <th style="width:45%">Bidder</th>
                            <th class="text-end" style="width:30%">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bids as $bid)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($bid->created_at)->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    @if($auction->anonymize_bidders)
                                        {{ $this->anonymize($bid->user) }}
                                    @else
                                        {{ $bid->user->name ?? 'User' }}
                                    @endif
                                </td>
                                <td class="text-end">₦{{ number_format($bid->amount, 2) }}</td>
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

    {{-- End Now Modal --}}
    <div wire:ignore.self class="modal fade" id="endModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-warning">
                        <i class="fa-solid fa-stopwatch me-1"></i> End Auction Now
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to mark this auction as <strong>Ended</strong> immediately?
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" wire:click="endNowConfirmed"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="endNowConfirmed">
                            <i class="fa-solid fa-check me-1"></i> Yes, end now
                        </span>
                        <span wire:loading wire:target="endNowConfirmed">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Ending...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Extend Modal --}}
    <div wire:ignore.self class="modal fade" id="extendModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i> Extend Auction
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Extend by (minutes)</label>
                    <input type="number" min="1" class="form-control" wire:model.live="extendMinutes">
                    <div class="text-muted small mt-2">
                        You can extend scheduled or live auctions.
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" wire:click="extendConfirmed"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="extendConfirmed">
                            <i class="fa-solid fa-check me-1"></i> Extend
                        </span>
                        <span wire:loading wire:target="extendConfirmed">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Working...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
    <script>
        (function () {
            function pad(n) { return n < 10 ? '0' + n : '' + n; }

            function renderCountdown(el) {
                const start = parseInt(el.dataset.start, 10);
                const end = parseInt(el.dataset.end, 10);
                const now0 = parseInt(el.dataset.now, 10); // server now snapshot
                const skew = Date.now() / 1000 - now0;       // approximate client-server offset in seconds

                function tick() {
                    const now = Date.now() / 1000 - skew;
                    let text = '—';
                    if (now < start) {
                        const diff = Math.max(0, Math.floor(start - now));
                        text = 'Starts in ' + fmt(diff);
                    } else if (now >= start && now < end) {
                        const diff = Math.max(0, Math.floor(end - now));
                        text = 'Ends in ' + fmt(diff);
                    } else {
                        text = 'Ended';
                    }
                    el.textContent = text;
                }
                function fmt(sec) {
                    const d = Math.floor(sec / 86400);
                    const h = Math.floor((sec % 86400) / 3600);
                    const m = Math.floor((sec % 3600) / 60);
                    const s = Math.floor(sec % 60);
                    if (d > 0) return `${d}d ${pad(h)}h ${pad(m)}m ${pad(s)}s`;
                    if (h > 0) return `${h}h ${pad(m)}m ${pad(s)}s`;
                    return `${m}m ${pad(s)}s`;
                }
                tick();
                return setInterval(tick, 1000);
            }

            document.addEventListener('DOMContentLoaded', () => {
                const el = document.getElementById('countdown');
                if (el) renderCountdown(el);
            });

            window.addEventListener('show-end-modal', () => {
                new bootstrap.Modal(document.getElementById('endModal')).show();
            });
            window.addEventListener('hide-end-modal', () => {
                const m = bootstrap.Modal.getInstance(document.getElementById('endModal'));
                m && m.hide();
            });

            window.addEventListener('show-extend-modal', () => {
                new bootstrap.Modal(document.getElementById('extendModal')).show();
            });
            window.addEventListener('hide-extend-modal', () => {
                const m = bootstrap.Modal.getInstance(document.getElementById('extendModal'));
                m && m.hide();
            });
        })();
    </script>
@endpush