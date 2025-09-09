<style>
    :root{
        --gm-radius-lg:16px;
        --gm-radius-md:12px;
        --gm-shadow:0 8px 24px rgba(16,24,40,.05);
        --gm-shell-max:1140px; /* overall page width (nicer than full-bleed) */
        --gm-shell-pad: clamp(18px, 3vw, 36px); /* side padding */
    }

    /* Page shell for comfy side padding & width */
    .gm-shell{
        max-width: var(--gm-shell-max);
        margin-inline:auto;
        padding-inline: var(--gm-shell-pad);
    }

    /* Cards & sections */
    .gm-card{
        background:#fff;
        border:1px solid #eef0f2;
        border-radius:var(--gm-radius-lg);
        box-shadow:var(--gm-shadow);
    }

    /* Header */
    .section-header h2{ letter-spacing:.2px; }
    .section-header .subtitle{ color:#6b7280; }

    /* Filters toolbar */
    .toolbar .form-label{ font-size:.8rem; color:#6b7280; }
    .toolbar .form-control, .toolbar .form-select{
        border-radius:var(--gm-radius-md);
    }

    /* Table polish */
    .orders-table thead th{
        background:#f8fafc !important;
        border-bottom:1px solid #eef0f2 !important;
        color:#111827;
        font-weight:600;
        white-space:nowrap; /* keep headers on one row */
    }
    .orders-table{
        min-width: 1080px; /* ensures headers never wrap; enables horizontal scroll in .table-responsive */
    }
    .orders-table td, .orders-table th{ vertical-align:middle; }

    /* Column sizing for balance on desktop */
    .orders-table th.col-id{ width:90px; }
    .orders-table th.col-items{ width:90px; }
    .orders-table th.col-total{ width:140px; }
    .orders-table th.col-pay{ width:190px; }
    .orders-table th.col-status{ width:120px; }
    .orders-table th.col-placed{ width:170px; }
    .orders-table th.col-actions{ width:150px; }

    /* Truncation helpers */
    .truncate-1{
        display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        max-width: 420px;
    }

    /* Toasts (flash) */
    .gm-toast-wrap{
        position:fixed; top:16px; left:50%; transform:translateX(-50%);
        width:min(560px, 92vw); z-index:1080;
        padding-inline: var(--gm-shell-pad);
    }
    .gm-toast{
        border-radius:var(--gm-radius-md);
        box-shadow:0 10px 30px rgba(16,24,40,.18);
    }

    /* Mobile list cards */
    @media (max-width: 767.98px){
        .gm-mobile .card{ border:1px solid #eef0f2; border-radius:var(--gm-radius-lg); }
        .truncate-1{ max-width: 100%; }
    }

    /* Your existing button color tweak */
    .btn-outline-success { color: var(--bs-success) !important; }
    .btn-outline-success:hover { color: #fff !important; }
</style>

<div class="py-72">
    <div class="container container-lg">
        <div class="gm-shell">

            {{-- Flash toasts --}}
            <div class="gm-toast-wrap">
                @if (session()->has('success'))
                    <div class="alert alert-success gm-toast d-flex align-items-start justify-content-between" role="alert">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ph-fill ph-check-circle mt-1"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>
                    </div>
                    <script>setTimeout(()=>document.querySelector('.gm-toast .btn-close')?.click(),2200);</script>
                @endif
                @if (session()->has('error'))
                    <div class="alert alert-danger gm-toast d-flex align-items-start justify-content-between" role="alert">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ph-fill ph-x-circle mt-1"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            </div>

            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-end section-header mb-16">
                <div>
                    <h2 class="h3 fw-bold mb-1">My Orders</h2>
                    <div class="subtitle">Track purchases, download invoices, and follow delivery updates.</div>
                </div>
                <a href="{{ route('store.shop') }}" class="btn btn-sm btn-outline-secondary rounded-3">
                    <i class="ph ph-arrow-left"></i> Continue Shopping
                </a>
            </div>

            {{-- Filters --}}
            <div class="gm-card mb-20">
                <div class="card-body p-16 toolbar">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4 col-lg-4">
                            <label class="form-label mb-1">Search</label>
                            <input type="text" class="form-control" placeholder="Order #, product, vendor" wire:model.live="search">
                        </div>
                        <div class="col-md-2 col-lg-2">
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
                        <div class="col-md-3 col-lg-3">
                            <label class="form-label mb-1">From</label>
                            <input type="date" class="form-control" wire:model.live="dateFrom">
                        </div>
                        <div class="col-md-3 col-lg-3">
                            <label class="form-label mb-1">To</label>
                            <input type="date" class="form-control" wire:model.live="dateTo">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Desktop table --}}
            <div class="d-none d-md-block gm-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle orders-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="col-id">#</th>
                                <th>Vendor(s)</th>
                                <th class="col-items">Items</th>
                                <th class="col-total">Total</th>
                                <th class="col-pay">Payment</th>
                                <th class="col-status">Status</th>
                                <th class="col-placed">Placed</th>
                                <th class="text-end col-actions">Actions</th>
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
                                    <td class="fw-semibold">#{{ $o->id }}</td>
                                    <td><span class="truncate-1" title="{{ $vend }}">{{ $vend ?: '—' }}</span></td>
                                    <td>{{ $itemsCount }}</td>
                                    <td>{{ $sym }}{{ number_format((float) $o->total_amount, 2) }}</td>
                                    <td><span class="badge rounded-pill {{ $payBadge }}">{{ $pm }} / {{ $ps }}</span></td>
                                    <td><span class="badge rounded-pill {{ $badgeClass }}">{{ ucfirst($status) }}</span></td>
                                    <td>{{ $o->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 text-success rounded-3"
                                           href="{{ route('orders.invoice', $o->id) }}">
                                            <i class="ph ph-download-simple"></i><span>Invoice</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-48 text-muted">
                                        <div class="d-flex flex-column align-items-center gap-2">
                                            <i class="ph ph-shopping-bag-open" style="font-size:36px; color:#94a3b8;"></i>
                                            <div class="fw-semibold">No orders yet</div>
                                            <div class="small">You haven’t placed any orders. Find something you love!</div>
                                            <a href="{{ route('store.shop') }}" class="btn btn-primary btn-sm mt-2 rounded-3">Browse Shop</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-16 py-12">
                    {{ $orders->links() }}
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="d-md-none gm-mobile">
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
                    <div class="card mb-12">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-6">
                                <div class="fw-semibold">Order #{{ $o->id }}</div>
                                <span class="badge rounded-pill {{ $badgeClass }}">{{ ucfirst($status) }}</span>
                            </div>
                            <div class="small text-muted mb-2">{{ $o->created_at->format('Y-m-d H:i') }}</div>
                            <div class="mb-2">
                                <div class="text-muted small">Vendor(s)</div>
                                <div class="fw-medium">{{ $vend ?: '—' }}</div>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <div><span class="text-muted small">Items</span><div class="fw-semibold">{{ $itemsCount }}</div></div>
                                <div><span class="text-muted small">Total</span><div class="fw-semibold">{{ $sym }}{{ number_format((float) $o->total_amount, 2) }}</div></div>
                            </div>
                            <div class="mt-2">
                                <span class="badge rounded-pill {{ $payBadge }}">{{ $pm }} / {{ $ps }}</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-end">
                            <a class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 text-success rounded-3"
                               href="{{ route('orders.invoice', $o->id) }}">
                                <i class="ph ph-download-simple"></i><span>Invoice</span>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="gm-card text-center py-40">
                        <div class="d-flex flex-column align-items-center gap-2">
                            <i class="ph ph-shopping-bag-open" style="font-size:36px; color:#94a3b8;"></i>
                            <div class="fw-semibold">No orders yet</div>
                            <div class="small">You haven’t placed any orders. Find something you love!</div>
                            <a href="{{ route('store.shop') }}" class="btn btn-primary btn-sm mt-2 rounded-3">Browse Shop</a>
                        </div>
                    </div>
                @endforelse

                <div class="mt-12">
                    {{ $orders->links() }}
                </div>
            </div>

            {{-- Order Detail Modal (unchanged logic) --}}
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
                                <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="closeOrder"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="fw-semibold mb-1">Bill To</div>
                                            <div>{{ $customerName ?: '—' }}</div>
                                            @if(!empty($addr['street'] ?? $addr['street_address'] ?? null))
                                                <div class="text-muted small">{{ $addr['street'] ?? $addr['street_address'] }}</div>
                                            @endif
                                            <div class="text-muted small">{{ $addr['city'] ?? '' }} {{ $addr['state'] ?? '' }}</div>
                                            @if(!empty($addr['email'])) <div class="text-muted small">{{ $addr['email'] }}</div> @endif
                                            @if(!empty($addr['phone'])) <div class="text-muted small">{{ $addr['phone'] }}</div> @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="fw-semibold mb-1">Order Info</div>
                                            <div class="text-muted small">Placed: {{ $o->created_at->format('Y-m-d H:i') }}</div>
                                            @if($o->payment_method)
                                                <div class="text-muted small">Payment: {{ ucfirst($o->payment_method) }}</div>
                                            @endif
                                            @if($o->payment_status)
                                                <div class="text-muted small">Payment Status: {{ ucfirst($o->payment_status) }}</div>
                                            @endif
                                            @if($o->external_payment_id)
                                                <div class="text-muted small">Ref: {{ $o->external_payment_id }}</div>
                                            @endif
                                            <div class="text-muted small">Currency: {{ strtoupper($o->currency ?? 'USD') }}</div>
                                        </div>
                                    </div>
                                </div>

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
                        @if($label) <div class="text-muted small">{{ $label }}</div> @endif
                    </td>
                    <td>{{ $vendor }}</td>
                    <td class="text-center">{{ (int) $it->quantity }}</td>
                    <td class="text-end">{{ $sym }}{{ number_format((float) $it->unit_price, 2) }}</td>
                    <td class="text-end">{{ $sym }}{{ number_format((float) $it->total_price, 2) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-md-6"></div>
                                    <div class="col-md-6">
                                        <table class="table table-sm mb-0">
                                            <tr>
                                                <td>Subtotal</td>
                                                <td class="text-end">{{ $sym }}{{ number_format((float) ($o->subtotal_amount ?? 0), 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td>Shipping</td>
                                                <td class="text-end">{{ $sym }}{{ number_format((float) ($o->shipping_amount ?? 0), 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td>Discount</td>
                                                <td class="text-end">- {{ $sym }}{{ number_format((float) ($o->discount_total ?? 0), 2) }}</td>
                                            </tr>
                                            <tr class="fw-semibold">
                                                <td>Grand Total</td>
                                                <td class="text-end">{{ $sym }}{{ number_format((float) ($o->total_amount ?? 0), 2) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <a href="{{ route('orders.invoice', $o->id) }}" class="btn btn-outline-secondary rounded-3">
                                    <i class="ph ph-download-simple"></i> Download Invoice
                                </a>
                                <button type="button" class="btn btn-primary rounded-3" data-bs-dismiss="modal" wire:click="closeOrder">
                                    Close
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
