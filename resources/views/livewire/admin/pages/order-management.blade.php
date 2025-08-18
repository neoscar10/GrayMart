<div>
  <div class="d-flex justify-content-between align-items-center pt-4 mb-3">
    <h2>Order Management</h2>
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
      <select class="form-select" wire:model.live="vendorFilter">
        <option value="">All Vendors</option>
        @foreach($vendors as $v)
          <option value="{{ $v->id }}">{{ $v->name }}</option>
        @endforeach
      </select>
    </div>
    {{-- <div class="col-md-2">
      <select class="form-select" wire:model.live="customerFilter">
        <option value="">All Customers</option>
        @foreach($customers as $c)
          <option value="{{ $c->id }}">{{ $c->name }}</option>
        @endforeach
      </select>
    </div> --}}
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

  {{-- Orders Table --}}
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Customer</th>
          <th>Email</th>
          <th>Vendor(s)</th>
          <th>Total</th>
          <th>Status</th>
          <th>Placed At</th>
          <th width="100">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($orders as $order)
            <tr>
            <td class="no-wrap">{{ $order->id }}</td>
            <td>{{ $order->customer->name }}</td>
            <td>{{ $order->customer->email }}</td>
            <td>{{ $order->items->pluck('vendor.name')->unique()->filter()->implode(', ') }}
      </td>
            <td>${{ number_format($order->total_amount, 2) }}</td>
            <td>
              <span class="badge bg-{{ 
              $order->status === 'pending' ? 'warning text-dark' :
    ($order->status === 'processing' ? 'primary' :
      ($order->status === 'shipped' ? 'info' :
        ($order->status === 'delivered' ? 'success' : 'danger')))
              }}">
              {{ ucfirst($order->status) }}
              </span>
            </td>
            <td class="no-wrap">{{ $order->created_at->format('Y-m-d H:i') }}</td>
            <td class="no-wrap">
              <button wire:click="openOrderModal({{ $order->id }})"
                  class="btn btn-sm btn-outline-primary"
                  title="View details">
              <i class="fa-solid fa-eye"></i>
              </button>
            </td>
            </tr>
    @empty
          <tr><td colspan="8" class="text-center">No orders found.</td></tr>
        @endforelse
      </tbody>
    </table>
    {{ $orders->links() }}
  </div>

  {{-- Order Detail + Admin‑Note Modal --}}
  <div wire:ignore.self class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        @if($selectedOrder)
        <div class="modal-header">
        <h5 class="modal-title">Order #{{ $selectedOrder->id }} Details</h5>
        <button type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            wire:click="closeOrderModal"></button>
        </div>
        <div class="modal-body">
        <p><strong>Customer:</strong> {{ $selectedOrder->customer->name }} - {{ $selectedOrder->customer->email }}</p>
        <p><strong>Vendor(s):</strong>
          @if($selectedOrder->vendors && $selectedOrder->vendors->count())
        @foreach($selectedOrder->vendors as $v)
        {{ $v->name }} - {{ $v->email }}@if(!$loop->last), @endif
      @endforeach
      @else
        —
      @endif
        </p>

        <p><strong>Placed At:</strong> {{ $selectedOrder->created_at->format('Y-m-d H:i') }}</p>
        <hr>
        <h6>Items</h6>
        <table class="table">
          <thead>
          <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Price</th>
          </tr>
          </thead>
          <tbody>
          @foreach($selectedOrder->items as $item)
            <tr>
            <td>{{ $item->product->name }}</td>
            <td>{{ $item->quantity }}</td>
            <td>${{ number_format($item->unit_price, 2) }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
        <p class="text-end"><strong>Total:</strong> ${{ number_format($selectedOrder->total_amount, 2) }}</p>

        {{-- Admin Note --}}
        <hr>
        {{-- inside your Order‑Detail modal's body --}}
        <div class="mb-3">
        <label>Admin Note</label>
        <textarea
          class="form-control"
          rows="3"
          wire:model.defer="adminNote"
        ></textarea>
        @error('adminNote') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <button wire:click="saveAdminNote" class="btn btn-primary">
        Save Note
        </button>

        <button wire:click="closeOrderModal"
            class="btn btn-secondary"
            data-bs-dismiss="modal">
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
    Livewire.on('showOrderModal', () => {
      new bootstrap.Modal('#orderDetailModal').show();
    });
    Livewire.on('hideOrderModal', () => {
      bootstrap.Modal.getInstance('#orderDetailModal').hide();
    });
  });
</script>
@endpush

