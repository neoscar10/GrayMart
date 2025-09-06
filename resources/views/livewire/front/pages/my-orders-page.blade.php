<style>
    .btn-outline-success {
        color: var(--bs-success) !important;
    }

    .btn-outline-success:hover {
        color: #fff !important;
    }
</style>

<div class="py-80">
    <div class="container container-lg">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h3 fw-bold mb-0">My Orders</h2>
            <a href="{{ route('store.shop') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ph ph-arrow-left"></i> Continue Shopping
            </a>
        </div>

        {{-- Flash --}}
        @if (session()->has('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session()->has('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- Filters --}}
        <div class="card border-0 shadow-sm rounded-16 mb-4">
            <div class="card-body p-16">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Search</label>
                        <input type="text" class="form-control" placeholder="Order #, product, vendor"
                            wire:model.live="search">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Status</label>
                        <select class="form-select" wire:model.live="status">
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">From</label>
                        <input type="date" class="form-control" wire:model.live="dateFrom">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">To</label>
                        <input type="date" class="form-control" wire:model.live="dateTo">
                    </div>
                </div>
            </div>
        </div>

        {{-- Orders Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Vendor(s)</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Placed</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $row)
                                            @php
    /** @var \App\Models\Order $o */
    $o = $row['order'];
    $sym = $row['symbol'];
    $vend = $row['vendors_string'];
    $itemsCount = $o->items->sum('quantity');
    $pm = $o->payment_method ? ucfirst($o->payment_method) : '—';
    $ps = $o->payment_status ? ucfirst($o->payment_status) : '—';
    $status = $o->status ?? 'pending';

    $badgeClass = match ($status) {
        'pending' => 'bg-warning text-dark',
        'processing' => 'bg-primary',
        'shipped' => 'bg-info text-dark',
        'delivered' => 'bg-success',
        'cancelled' => 'bg-danger',
        default => 'bg-secondary',
    };

    $payBadge = match (strtolower($o->payment_status ?? 'unpaid')) {
        'paid' => 'bg-success',
        'unpaid' => 'bg-warning text-dark',
        'failed' => 'bg-danger',
        'refunded' => 'bg-secondary',
        default => 'bg-light text-dark',
    };
                                            @endphp
                                            <tr>
                                                <td class="fw-semibold">{{ $o->id }}</td>
                                                <td class="text-truncate" style="max-width:260px">{{ $vend ?: '—' }}</td>
                                                <td>{{ $itemsCount }}</td>
                                                <td>{{ $sym }}{{ number_format((float) $o->total_amount, 2) }}</td>
                                                <td>
                                                    <span class="badge {{ $payBadge }}">{{ $pm }} / {{ $ps }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span>
                                                </td>
                                                <td>{{ $o->created_at->format('Y-m-d H:i') }}</td>
                                                <td class="text-end">
                                                    <div class="btn-group">
                                                        <a class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 text-success"
                                                            href="{{ route('orders.invoice', $o->id) }}">
                                                            <i class="ph ph-download-simple"></i>
                                                            <span>Invoice</span>
                                                        </a>
                                                    </div>
                                                </td>

                                            </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No orders yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $orders->links() }}
        </div>

        {{-- Order Detail Modal --}}
        <div wire:ignore.self class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    @if($selectedOrder)
                        @php
    $o = $selectedOrder;
    $sym = strtoupper($o->currency ?? 'USD') === 'NGN' ? '₦' : (strtoupper($o->currency ?? 'USD') === 'USD' ? '$' : strtoupper($o->currency ?? 'USD') . ' ');
    $addr = $o->shipping_address ?? [];
    $customerName = optional($o->customer)->name ?: trim(($addr['first_name'] ?? '') . ' ' . ($addr['last_name'] ?? ''));
                        @endphp

                        <div class="modal-header">
                            <h5 class="modal-title">Order #{{ $o->id }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                wire:click="closeOrder"></button>
                        </div>

                        <div class="modal-body">
                            {{-- Top summary boxes --}}
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100">
                                        <div class="fw-semibold mb-1">Bill To</div>
                                        <div>{{ $customerName ?: '—' }}</div>
                                        @if(!empty($addr['street'] ?? $addr['street_address'] ?? null))
                                            <div class="text-muted small">{{ $addr['street'] ?? $addr['street_address'] }}</div>
                                        @endif
                                        <div class="text-muted small">{{ $addr['city'] ?? '' }} {{ $addr['state'] ?? '' }}
                                        </div>
                                        @if(!empty($addr['email']))
                                        <div class="text-muted small">{{ $addr['email'] }}</div> @endif
                                        @if(!empty($addr['phone']))
                                        <div class="text-muted small">{{ $addr['phone'] }}</div> @endif
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="border rounded-3 p-3 h-100">
                                        <div class="fw-semibold mb-1">Order Info</div>
                                        <div class="text-muted small">Placed: {{ $o->created_at->format('Y-m-d H:i') }}
                                        </div>
                                        @if($o->payment_method)
                                            <div class="text-muted small">Payment: {{ ucfirst($o->payment_method) }}</div>
                                        @endif
                                        @if($o->payment_status)
                                            <div class="text-muted small">Payment Status: {{ ucfirst($o->payment_status) }}
                                            </div>
                                        @endif
                                        @if($o->external_payment_id)
                                            <div class="text-muted small">Ref: {{ $o->external_payment_id }}</div>
                                        @endif
                                        <div class="text-muted small">Currency: {{ strtoupper($o->currency ?? 'USD') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Items --}}
                            <div class="mt-3">
                                <h6 class="mb-2">Items</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Product</th>
                                                <th>Vendor</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-end">Unit</th>
                                                <th class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($o->items as $it)
                                                @php
        $name = $it->product_name ?: optional($it->product)->name ?: 'Item';
        $label = $it->meta['variant_label'] ?? null;
        $vendor = $it->vendor?->name ?: '—';
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <div class="fw-medium">{{ $name }}</div>
                                                        @if($label)
                                                            <div class="text-muted small">{{ $label }}</div>
                                                        @endif
                                                    </td>
                                                    <td>{{ $vendor }}</td>
                                                    <td class="text-center">{{ (int) $it->quantity }}</td>
                                                    <td class="text-end">
                                                        {{ $sym }}{{ number_format((float) $it->unit_price, 2) }}</td>
                                                    <td class="text-end">
                                                        {{ $sym }}{{ number_format((float) $it->total_price, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Totals --}}
                            <div class="row mt-2">
                                <div class="col-md-6"></div>
                                <div class="col-md-6">
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <td>Subtotal</td>
                                            <td class="text-end">
                                                {{ $sym }}{{ number_format((float) ($o->subtotal_amount ?? 0), 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Shipping</td>
                                            <td class="text-end">
                                                {{ $sym }}{{ number_format((float) ($o->shipping_amount ?? 0), 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Discount</td>
                                            <td class="text-end">-
                                                {{ $sym }}{{ number_format((float) ($o->discount_total ?? 0), 2) }}</td>
                                        </tr>
                                        <tr class="fw-semibold">
                                            <td>Grand Total</td>
                                            <td class="text-end">
                                                {{ $sym }}{{ number_format((float) ($o->total_amount ?? 0), 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <a href="{{ route('orders.invoice', $o->id) }}" class="btn btn-outline-secondary">
                                <i class="ph ph-download-simple"></i> Download Invoice
                            </a>
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal" wire:click="closeOrder">
                                Close
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Livewire.on('showOrderModal', () => new bootstrap.Modal('#orderDetailModal').show());
                Livewire.on('hideOrderModal', () => bootstrap.Modal.getInstance('#orderDetailModal')?.hide());
            });
        </script>
    @endpush
</div>