<div class="py-80">
    <div class="container container-lg">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-16">
                    <div class="card-body p-24 text-center">
                        @if($ok)
                            <div class="mb-12">
                                <span
                                    class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success text-white"
                                    style="width:64px;height:64px;">
                                    <i class="ph ph-check text-white" style="font-size:32px;"></i>
                                </span>
                            </div>
                            <h3 class="mb-6">Payment Successful</h3>
                            <p class="text-muted mb-16">{{ $message }}</p>

                            <div class="border rounded-12 p-16 text-start mb-16">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold">Total Paid</span>
                                    <span class="fw-bold">${{ number_format($grand, 2) }}</span>
                                </div>
                            </div>

                            <div class="text-start">
                                <h6 class="mb-10">Orders</h6>
                                <div class="row g-12">
                                    @foreach($vendorCards as $card)
                                        <div class="col-md-6">
                                            <div class="border rounded-12 p-12 h-100">
                                                <div class="d-flex align-items-center gap-10 mb-8">
                                                    @if(!empty($card['logo']))
                                                        <img src="{{ $card['logo'] }}" alt="logo" width="36" height="36"
                                                            class="rounded-circle object-fit-cover">
                                                    @else
                                                        <div class="rounded-circle bg-gray-200" style="width:36px;height:36px;">
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div class="fw-semibold">{{ $card['store_name'] }}</div>
                                                        <small class="text-muted">Order #{{ $card['order_id'] }}</small>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <small class="text-muted">Amount</small>
                                                    <small class="fw-semibold">${{ number_format($card['total'], 2) }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="d-flex gap-10 justify-content-center mt-20">
                                <a href=""
                                    class="btn bg-main-600 hover-bg-main-700 text-white rounded-12 px-16">View My Orders</a>
                                <a href="{{ route('account.orders') }}" class="btn btn-outline-secondary rounded-12 px-16">Continue
                                    Shopping</a>
                            </div>
                        @else
                            <div class="mb-12">
                                <span
                                    class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning text-white"
                                    style="width:64px;height:64px;">
                                    <i class="ph ph-warning text-white" style="font-size:32px;"></i>
                                </span>
                            </div>
                            <h3 class="mb-6">Payment Pending</h3>
                            <p class="text-muted mb-16">{{ $message }}</p>

                            <div class="d-flex gap-10 justify-content-center mt-8">
                                <a href="{{ route('shop') }}" class="btn btn-outline-secondary rounded-12 px-16">Back to
                                    Shop</a>
                                <a href="{{ route('checkout') }}"
                                    class="btn bg-main-600 hover-bg-main-700 text-white rounded-12 px-16">Try Checkout
                                    Again</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>