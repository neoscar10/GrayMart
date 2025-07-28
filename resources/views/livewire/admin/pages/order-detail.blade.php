<div class="p-4">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Order #{{ $order->id }} Details</h3>
    <a href="{{ route('admin.order-management') }}" class="btn btn-outline-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> Back to Orders
    </a>
  </div>

  {{-- Flash --}}
  @if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row mb-4">
    <div class="col-md-4">
      <h5>Customer</h5>
      <p>
        {{ $order->customer->name }}<br>
        {{ $order->customer->email }}
      </p>
    </div>
    <div class="col-md-4">
      <h5>Vendor</h5>
      <p>
        {{ $order->vendor->name }}<br>
        {{ $order->vendor->email }}
      </p>
    </div>
    <div class="col-md-4">
      <h5>Placed At</h5>
      <p>{{ $order->created_at->format('Y-m-d H:i') }}</p>
    </div>
  </div>

  {{-- Items --}}
  <div class="table-responsive mb-4">
    <table class="table table-bordered">
      <thead class="table-light">
        <tr>
          <th>Product</th>
          <th class="text-center">Qty</th>
          <th class="text-end">Unit Price</th>
          <th class="text-end">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        @foreach($order->items as $item)
          <tr>
            <td>{{ $item->product->name }}</td>
            <td class="text-center">{{ $item->quantity }}</td>
            <td class="text-end">${{ number_format($item->unit_price,2) }}</td>
            <td class="text-end">${{ number_format($item->quantity * $item->unit_price,2) }}</td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <th colspan="3" class="text-end">Total</th>
          <th class="text-end">${{ number_format($order->total_amount,2) }}</th>
        </tr>
      </tfoot>
    </table>
  </div>

  {{-- Status / Tracking / Admin Notes --}}
  <form wire:submit.prevent="updateOrder">
    <div class="row g-3">

      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select class="form-select" wire:model.defer="status">
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="shipped">Shipped</option>
          <option value="delivered">Delivered</option>
          <option value="cancelled">Cancelled</option>
        </select>
        @error('status') <div class="text-danger">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-4">
        <label class="form-label">Tracking #</label>
        <input type="text" class="form-control"
               wire:model.defer="tracking_number">
        @error('tracking_number') <div class="text-danger">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-4">
        <label class="form-label">Placed At</label>
        <input type="text" class="form-control"
               value="{{ $order->created_at->format('Y-m-d H:i') }}" readonly>
      </div>

      <div class="col-md-12">
        <label class="form-label">Admin Notes / Issue Resolution</label>
        <textarea class="form-control" rows="4"
                  wire:model.defer="admin_notes"></textarea>
        @error('admin_notes') <div class="text-danger">{{ $message }}</div> @enderror
      </div>

    </div>

    <div class="mt-4 text-end">
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-save me-1"></i> Save Changes
      </button>
    </div>
  </form>

</div>

@push('styles')
<style>
  .table td, .table th { vertical-align: middle; }
</style>
@endpush
