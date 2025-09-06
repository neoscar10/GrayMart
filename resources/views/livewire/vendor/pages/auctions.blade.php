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

            <select class="form-select" style="max-width:160px" wire:model.live="statusFilter">
                <option value="">Any Status</option>
                <option value="scheduled">Scheduled</option>
                <option value="live">Live</option>
                <option value="ended">Ended</option>
            </select>

            <button class="btn btn-primary text-nowrap" wire:click="openCreate({{ $product ?? 'null' }})">
                <i class="fa-solid fa-plus me-1"></i> New Auction
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 auctions-table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-truncate" style="width:18%">Product</th>
                            <th class="text-truncate" style="width:13%">Starts</th>
                            <th class="text-truncate" style="width:13%">Ends</th>
                            <th class="text-end text-truncate" style="width:13%">Highest</th>
                            <th class="text-truncate" style="width:8%" title="Anonymize bidders">Anon</th>
                            <th class="text-truncate" style="width:12%" title="Anti-sniping window (minutes)">Anti-snipe
                            </th>
                            <th class="text-truncate" style="width:10%">Status</th>
                            <th class="text-end" style="width:23%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auctions as $a)
                                                                <tr class="{{ $a->derived_status === 'live' ? 'table-success' : '' }}">
                                                                    <td class="text-truncate" title="{{ $a->product->name ?? '' }}">
                                                                        {{ $a->product->name ?? '—' }}</td>
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
                                                                            class="badge {{ $a->anonymize_bidders ? 'bg-info text-dark' : 'bg-dark text-light' }}"
                                                                            title="Anonymize bidders">
                                                                            {{ $a->anonymize_bidders ? 'Yes' : 'No' }}
                                                                        </span>
                                                                    </td>
                                                                    <td>{{ (int) $a->anti_sniping_window }}</td>
                                                                    <td>
                                                                        <span class="badge
                                                            {{ $a->derived_status === 'live' ? 'bg-success' :
                            ($a->derived_status === 'scheduled' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                                                            {{ ucfirst($a->derived_status) }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <div class="btn-group">
                                                                            <button class="btn btn-sm btn-outline-secondary text-nowrap"
                                                                                wire:click="openEdit({{ $a->id }})" title="Edit">
                                                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                                                            </button>
                                                                            <a class="btn btn-sm btn-outline-primary text-nowrap" href="{{ route('vendor.auction-details', $a->id) }}">
                                                                                <i class="fa-solid fa-eye"></i> View
                                                                            </a>


                                                                            @if($a->derived_status !== 'ended')
                                                                                <button class="btn btn-sm btn-outline-warning text-nowrap"
                                                                                    wire:click="confirmEndNow({{ $a->id }})" title="End now">
                                                                                    <i class="fa-solid fa-stopwatch"></i> End
                                                                                </button>
                                                                            @endif

                                                                            <button class="btn btn-sm btn-outline-danger text-nowrap"
                                                                                wire:click="confirmDelete({{ $a->id }})" title="Delete">
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
                                <small class="text-muted d-block mt-1">
                                    Only approved, active & reserved products are listed.
                                </small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Starts <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" wire:model.live="starts_at">
                                @error('starts_at') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Ends <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" wire:model.live="ends_at">
                                @error('ends_at') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Anti-snipe (minutes)</label>
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
                                    Status is automatic based on start/end: <strong>Scheduled</strong> (future),
                                    <strong>Live</strong> (during), <strong>Ended</strong> (past).
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

    {{-- Confirm End Modal --}}
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

    {{-- Confirm Delete Modal --}}
    <div wire:ignore.self class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Delete Auction
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this auction? This action cannot be undone.
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="deleteConfirmed"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="deleteConfirmed">
                            <i class="fa-solid fa-trash-can me-1"></i> Delete
                        </span>
                        <span wire:loading wire:target="deleteConfirmed">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Deleting...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('styles')
    <style>
        .auctions-table th,
        .auctions-table td {
            white-space: nowrap;
            vertical-align: middle;
        }

        .auctions-table .text-truncate {
            max-width: 100%;
        }
    </style>
@endpush

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

        window.addEventListener('show-delete-modal', () => {
            const el = document.getElementById('deleteModal');
            new bootstrap.Modal(el).show();
        });
        window.addEventListener('hide-delete-modal', () => {
            const el = document.getElementById('deleteModal');
            const m = bootstrap.Modal.getInstance(el);
            m && m.hide();
        });

        window.addEventListener('show-end-modal', () => {
            const el = document.getElementById('endModal');
            new bootstrap.Modal(el).show();
        });
        window.addEventListener('hide-end-modal', () => {
            const el = document.getElementById('endModal');
            const m = bootstrap.Modal.getInstance(el);
            m && m.hide();
        });
    </script>
@endpush