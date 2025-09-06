<div>
    <div class="d-flex justify-content-between align-items-center pt-4 mb-3">
        <h2>My Orders</h2>
        <button wire:click="exportCsv" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-file-csv me-1"></i> Export CSV
        </button>
    </div>

    {{-- Filters --}}
    <div class="row gx-2 gy-2 mb-3">
        <div class="col-md-3">
            <input type="text" class="form-control" placeholder="Search by order # or customer…"
                wire:model.live="search">
        </div>
        <div class="col-md-2">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        <div class="col-md-3 d-flex">
            <input type="date" class="form-control me-1" wire:model.live="dateFrom">
            <span class="px-2">to</span>
            <input type="date" class="form-control" wire:model.live="dateTo">
        </div>
    </div>

    {{-- Flash --}}
    @if(session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Orders Table --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Placed</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                                        <tr>
                                            <td class="no-wrap">{{ $order->id }}</td>
                                            <td>{{ $order->customer?->name ?? '—' }}</td>
                                            <td>{{ $order->customer?->email ?? '—' }}</td>
                                            <td>${{ number_format((float) $order->total_amount, 2) }}</td>
                                            <td>
                                                <span class="badge bg-{{ 
                                      ($order->payment_status === 'paid') ? 'success' :
                    (($order->payment_status === 'failed') ? 'danger' :
                        (($order->payment_status === 'refunded') ? 'secondary' : 'warning text-dark'))
                                    }}">
                                                    {{ ucfirst($order->payment_status ?? 'unpaid') }}
                                                </span>
                                            </td>
                                            <td>
                                                {{-- Quick change in table --}}
                                                <select class="form-select form-select-sm"
                                                    wire:change="changeStatus({{ $order->id }}, $event.target.value)">
                                                    @php $st = strtolower($order->status ?? 'pending'); @endphp
                                                    <option value="pending" {{ $st === 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="processing" {{ $st === 'processing' ? 'selected' : '' }}>Processing</option>
                                                    <option value="shipped" {{ $st === 'shipped' ? 'selected' : '' }}>Shipped</option>
                                                    <option value="delivered" {{ $st === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                                    <option value="cancelled" {{ $st === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                </select>
                                            </td>
                                            <td class="no-wrap">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                            <td class="no-wrap">
                                                <a href="{{ route('vendor.orders.invoice', $order->id) }}" class="btn btn-sm btn-outline-secondary me-1">
                                                    <i class="fa-solid fa-file-arrow-down"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-primary" wire:click="openOrderModal({{ $order->id }})">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            </td>

                                        </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $orders->links() }}
    </div>

    {{-- Detail Modal --}}
    <div wire:ignore.self class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                @if($selectedOrder)
                            <div class="modal-header">
                                <h5 class="modal-title">Order #{{ $selectedOrder->id }} Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    wire:click="closeOrderModal"></button>
                            </div>
                            <div class="modal-body">
                                {{-- Top meta --}}
                                <div class="row g-3 mb-2">
                                    <div class="col-md-6">
                                        <div><strong>Customer:</strong> {{ $selectedOrder->customer?->name ?? '—' }}</div>
                                        <div><strong>Email:</strong> {{ $selectedOrder->customer?->email ?? '—' }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div><strong>Placed:</strong> {{ $selectedOrder->created_at->format('Y-m-d H:i') }}</div>
                                        <div>
                                            <strong>Payment:</strong>
                                            <span class="badge bg-{{ 
                                ($selectedOrder->payment_status === 'paid') ? 'success' :
        (($selectedOrder->payment_status === 'failed') ? 'danger' :
            (($selectedOrder->payment_status === 'refunded') ? 'secondary' : 'warning text-dark'))
                              }}">
                                                {{ ucfirst($selectedOrder->payment_status ?? 'unpaid') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Address --}}
                                @php $addr = $selectedOrder->shipping_address ?? []; @endphp
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="mb-2">Shipping Address</h6>
                                    <div>{{ ($addr['first_name'] ?? '') . ' ' . ($addr['last_name'] ?? '') }}</div>
                                    <div>{{ $addr['street'] ?? '' }}</div>
                                    <div>{{ ($addr['city'] ?? '') . ' ' . ($addr['state'] ?? '') }}</div>
                                    <div>{{ $addr['phone'] ?? '' }} @if(!empty($addr['email'])) · {{ $addr['email'] }} @endif</div>
                                </div>

                                {{-- Items --}}
                                <h6 class="mb-2">Items</h6>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Product</th>
                                                <th>Variant</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">Unit</th>
                                                <th class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($selectedOrder->items as $item)
                                                @php
        $meta = $item->meta ?? [];
                                                  @endphp
                                                <tr>
                                                    <td>{{ $item->product?->name ?? $item->product_name ?? 'Product' }}</td>
                                                    <td class="text-muted small">{{ $meta['variant_label'] ?? '—' }}</td>
                                                    <td class="text-end">{{ (int) $item->quantity }}</td>
                                                    <td class="text-end">${{ number_format((float) $item->unit_price, 2) }}</td>
                                                    <td class="text-end">${{ number_format((float) $item->total_price, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Totals --}}
                                <div class="d-flex justify-content-end">
                                    <div class="text-end">
                                        <div>Subtotal:
                                            <strong>${{ number_format((float) $selectedOrder->subtotal_amount, 2) }}</strong></div>
                                        <div>Shipping:
                                            <strong>${{ number_format((float) $selectedOrder->shipping_amount, 2) }}</strong></div>
                                        @if((float) $selectedOrder->discount_total > 0)
                                            <div class="text-success">Discount: <strong>-
                                                    ${{ number_format((float) $selectedOrder->discount_total, 2) }}</strong></div>
                                        @endif
                                        <div class="mt-1 fs-5">Grand Total:
                                            <strong>${{ number_format((float) $selectedOrder->total_amount, 2) }}</strong></div>
                                    </div>
                                </div>

                                <hr>

                                {{-- Status update inside modal --}}
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-6">
                                        <label class="form-label">Update Status</label>
                                        <select class="form-select" wire:model.defer="selectedStatus">
                                            @php $st = strtolower($selectedOrder->status ?? 'pending'); @endphp
                                            <option value="pending" {{ $st === 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="processing" {{ $st === 'processing' ? 'selected' : '' }}>Processing
                                            </option>
                                            <option value="shipped" {{ $st === 'shipped' ? 'selected' : '' }}>Shipped</option>
                                            <option value="delivered" {{ $st === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                            <option value="cancelled" {{ $st === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <button class="btn btn-primary" wire:click="saveStatusFromModal">
                                            Save
                                        </button>
                                        <button class="btn btn-secondary" data-bs-dismiss="modal" wire:click="closeOrderModal">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .no-wrap {
            white-space: nowrap;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Livewire.on('showOrderModal', () => new bootstrap.Modal('#orderDetailModal').show());
            Livewire.on('hideOrderModal', () => bootstrap.Modal.getInstance('#orderDetailModal')?.hide());
        });
    </script>
@endpush