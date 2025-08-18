{{-- resources/views/livewire/vendor/pages/auctions/index.blade.php --}}
<div class="container-fluid py-4">

    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Auctions</h4>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <select class="form-select" style="max-width:220px" wire:model.live="product">
                <option value="">All Products</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>

            <select class="form-select" style="max-width:180px" wire:model.live="statusFilter">
                <option value="">Any Status</option>
                <option value="scheduled">Scheduled</option>
                <option value="running">Running</option>
                <option value="ended">Ended</option>
            </select>

            <button class="btn btn-primary text-nowrap" wire:click="openCreate({{ $product ?? 'null' }})">
                <i class="fa-solid fa-plus me-1"></i> New Auction
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Starts</th>
                        <th>Ends</th>
                        <th class="text-end">Highest Bid</th>
                        <th>Anon</th>
                        <th>Anti‑sniping (min)</th>
                        <th>Status</th>
                        <th class="text-end" style="width:220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auctions as $a)
                                    <tr>
                                        <td>{{ $a->product->name ?? '—' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($a->starts_at)->format('Y-m-d H:i') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($a->ends_at)->format('Y-m-d H:i') }}</td>
                                        <td class="text-end">
                                            @if(!is_null($a->highest_amount))
                                                ₦{{ number_format($a->highest_amount, 2) }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span
                                                class="badge {{ $a->anonymize_bidders ? 'bg-info text-dark' : 'bg-light text-dark' }}">
                                                {{ $a->anonymize_bidders ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        <td>{{ (int) $a->anti_sniping_window }}</td>
                                        <td>
                                            <span class="badge
                                  {{ $a->derived_status === 'running' ? 'bg-success' :
                        ($a->derived_status === 'scheduled' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                                {{ ucfirst($a->derived_status) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-secondary text-nowrap"
                                                    wire:click="openEdit({{ $a->id }})">
                                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                                </button>

                                                @if($a->derived_status !== 'ended')
                                                    <button class="btn btn-sm btn-outline-warning text-nowrap"
                                                        wire:click="endNow({{ $a->id }})">
                                                        <i class="fa-solid fa-stopwatch"></i> End Now
                                                    </button>
                                                @endif

                                                <button class="btn btn-sm btn-outline-danger text-nowrap"
                                                    wire:click="delete({{ $a->id }})">
                                                    <i class="fa-solid fa-trash-can"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No auctions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">
                @if($auctions->total())
                    Showing {{ $auctions->firstItem() }}–{{ $auctions->lastItem() }} of {{ $auctions->total() }}
                @else
                    No results
                @endif
            </div>
            <div class="d-flex align-items-center gap-2 w-100" style="max-width:none;">
                <div class="me-auto">
                    {{ $auctions->onEachSide(1)->links() }}
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted">Per page</span>
                    <select class="form-select form-select-sm" wire:model.live="perPage" style="width:auto;">
                        @foreach([10, 20, 30, 50] as $pp)
                            <option value="{{ $pp }}">{{ $pp }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    <div wire:ignore.self class="modal fade" id="auctionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form wire:submit.prevent="save">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-gavel me-1"></i>
                            {{ $auctionId ? 'Edit Auction' : 'New Auction' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Product <span class="text-danger">*</span></label>
                                <select class="form-select" wire:model.live="product_id">
                                    <option value="">Select product...</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                @error('product_id') <small class="text-danger">{{ $message }}</small> @enderror
                                <small class="text-muted d-block mt-1">Only approved, active & reserved products are
                                    listed.</small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Starts At <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" wire:model.live="starts_at">
                                @error('starts_at') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Ends At <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" wire:model.live="ends_at">
                                @error('ends_at') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Anti‑sniping Window (minutes)</label>
                                <input type="number" class="form-control" wire:model.live="anti_sniping_window" min="0"
                                    max="1440">
                                @error('anti_sniping_window') <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="anonCheck"
                                        wire:model.live="anonymize_bidders">
                                    <label class="form-check-label" for="anonCheck">Anonymize bidders</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <i class="fa-solid fa-circle-info me-1"></i>
                                    Status is set automatically based on start/end times:
                                    <strong>Scheduled</strong> (future), <strong>Running</strong> (live),
                                    <strong>Ended</strong> (past).
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="save">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save
                            </span>
                            <span wire:loading wire:target="save">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

@push('scripts')
    <script>
        window.addEventListener('show-auction-modal', () => {
            const el = document.getElementById('auctionModal');
            new bootstrap.Modal(el).show();
        });
        window.addEventListener('hide-auction-modal', () => {
            const el = document.getElementById('auctionModal');
            const m = bootstrap.Modal.getInstance(el);
            m && m.hide();
        });
    </script>
@endpush