<div class="py-80">
  <div class="container container-lg">
    <h2 class="h3 fw-bold mb-4">Checkout</h2>

    @if (session()->has('error'))
      <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session()->has('warning'))
      <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if (session()->has('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form wire:submit.prevent="placeOrder">
      <div class="row g-4">
        {{-- Left: Address + Payment --}}
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm mb-4 rounded-16">
            <div class="card-body p-20">
              <h5 class="mb-3">Shipping Address</h5>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">First Name</label>
                  <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                    wire:model.defer="first_name">
                  @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                  <label class="form-label">Last Name</label>
                  <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                    wire:model.defer="last_name">
                  @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label">Phone</label>
                  <input type="text" class="form-control @error('phone') is-invalid @enderror" wire:model.defer="phone">
                  @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                  <label class="form-label">Email (optional)</label>
                  <input type="email" class="form-control @error('email') is-invalid @enderror"
                    wire:model.defer="email">
                  @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                  <label class="form-label">Street</label>
                  <input type="text" class="form-control @error('street') is-invalid @enderror"
                    wire:model.defer="street">
                  @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                  <label class="form-label">City</label>
                  <input type="text" class="form-control @error('city') is-invalid @enderror" wire:model.defer="city">
                  @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                  <label class="form-label">State</label>
                  <input type="text" class="form-control @error('state') is-invalid @enderror" wire:model.defer="state">
                  @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <hr class="my-4">

              <h5 class="mb-3">Payment Method</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <input class="d-none" type="radio" id="pm-paypal" value="paypal" wire:model="payment_method">
                  <label for="pm-paypal" class="d-block border rounded-12 p-16 cursor-pointer hover-bg-main-50">
                    <div class="d-flex align-items-center justify-content-between">
                      <span class="fw-semibold">PayPal</span>
                      <i class="bi bi-paypal fs-4"></i>
                    </div>
                    <small class="text-muted">You’ll be redirected to PayPal to complete payment.</small>
                  </label>
                </div>
              </div>
              @error('payment_method') <div class="text-danger small mt-2">{{ $message }}</div> @enderror

              @if (empty(config('paypal')))
                <div class="alert alert-warning mt-3 mb-0">
                  PayPal is not configured yet. Orders will be created as <strong>unpaid</strong>.
                </div>
              @endif
            </div>
          </div>
        </div>

        {{-- Right: Order summary --}}
        <div class="col-lg-4">
          <div class="card border-0 shadow-sm rounded-16 mb-4">
            <div class="card-body p-20">
              <h5 class="mb-3">Order Summary</h5>

              {{-- Vendor groups --}}
              @foreach ($groups as $vid => $group)
                <div class="mb-3">
                  <div class="d-flex align-items-center gap-10 mb-2">
                    @if(!empty($group['vendor']['logo']))
                      <img src="{{ $group['vendor']['logo'] }}" alt="logo" width="28" height="28"
                        class="rounded-circle object-fit-cover">
                    @else
                      <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center"
                        style="width:28px;height:28px;">
                        <span class="small text-muted">S</span>
                      </div>
                    @endif
                    <div class="fw-semibold text-truncate">
                      {{ $group['vendor']['name'] ?? 'Store' }}
                    </div>
                  </div>

                  <ul class="list-group list-group-flush">
                    @foreach ($group['items'] as $line)
                      <li class="list-group-item px-0 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                          <img src="{{ $line['image_url'] }}" class="rounded me-2 object-fit-cover" width="42" height="42"
                            alt="">
                          <div>
                            <div class="fw-medium text-truncate" style="max-width:160px">{{ $line['display_name'] }}</div>
                            <small class="text-muted">
                              Qty: {{ $line['quantity'] }}
                              @if(!empty($line['variant_label'])) · {{ $line['variant_label'] }} @endif
                              @if(!empty($line['is_auction']) || !empty($line['meta']['is_auction']))
                                · Auction
                              @endif
                            </small>
                          </div>
                        </div>
                        <div class="fw-semibold">${{ number_format((float) $line['total_amount'], 2) }}</div>
                      </li>
                    @endforeach
                  </ul>

                  <div class="d-flex justify-content-between mt-2">
                    <small class="text-muted">Subtotal (vendor)</small>
                    <small class="fw-semibold">${{ number_format((float) $group['subtotal'], 2) }}</small>
                  </div>
                </div>
                @if (!$loop->last)
                  <hr>
                @endif
              @endforeach

              <hr class="my-3">
              <div class="d-flex justify-content-between">
                <span>Subtotal</span>
                <span class="fw-semibold">${{ number_format($subtotal, 2) }}</span>
              </div>
              <div class="d-flex justify-content-between">
                <span>Shipping</span>
                <span class="fw-semibold">${{ number_format($shipping, 2) }}</span>
              </div>
              @if ($discount > 0)
                <div class="d-flex justify-content-between text-success">
                  <span>Discount</span>
                  <span class="fw-semibold">- ${{ number_format($discount, 2) }}</span>
                </div>
              @endif
              <div class="d-flex justify-content-between mt-2">
                <span class="fw-bold">Grand Total</span>
                <span class="fw-bold">${{ number_format($grand, 2) }}</span>
              </div>
            </div>
          </div>

          <button type="submit" class="btn bg-main-600 hover-bg-main-700 text-white w-100 py-12 rounded-12"
            @disabled(empty($groups))>
            <span wire:loading.remove wire:target="placeOrder">
              Place Order
            </span>
            <span wire:loading wire:target="placeOrder">
              <i class="fas fa-spinner fa-spin me-1"></i> Processing…
            </span>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>