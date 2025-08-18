<div>
    <!-- ================================ Cart Section Start ================================ -->
    <section class="cart pt-40 pb-10">
        <div class="container container-lg">
            <div class="row gy-4">
                <div class="col-xl-8 col-lg-7">
                    <div class="cart-table border border-gray-100 rounded-8 px-40 py-48">
                        <div class="overflow-x-auto scroll-sm scroll-sm-horizontal">
                            <table class="table style-three">
                                <thead>
                                    <tr>
                                        <th class="h6 mb-0 text-lg fw-bold">Delete</th>
                                        <th class="h6 mb-0 text-lg fw-bold">Product Name</th>
                                        <th class="h6 mb-0 text-lg fw-bold">Price</th>
                                        <th class="h6 mb-0 text-lg fw-bold">Quantity</th>
                                        <th class="h6 mb-0 text-lg fw-bold">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($cart_items as $item)
                                        <tr>
                                            <!-- Remove Button -->
                                            <td class="align-middle">
                                                <button wire:click="removeItem({{ $item['product_id'] }})" type="button"
                                                    class="btn btn-outline-danger text-danger btn-sm d-flex align-items-center gap-2"
                                                    style="padding: 5px">
                                                    <span>Remove</span>
                                                </button>
                                            </td>



                                            <!-- Product Thumbnail and Info -->
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center gap-3">
                                                    <a href="#" class="border rounded p-1 d-inline-block"
                                                        style="width: 60px; height: 60px;">
                                                        <img src="{{ asset('storage/' . $item['image']) }}"
                                                            alt="Product Image" class="img-fluid"
                                                            style="object-fit: cover; width: 100%; height: 100%;">
                                                    </a>
                                                    <div>
                                                        <h6 class="mb-1">
                                                            <a href="#" class="text-decoration-none text-dark fw-semibold">
                                                                {{ $item['title'] }}
                                                            </a>
                                                        </h6>

                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Unit Price -->
                                            <td class="align-middle">
                                                <span
                                                    class="fw-semibold">${{ number_format($item['unit_amount'], 2) }}</span>
                                            </td>

                                            <!-- Quantity Control -->
                                            <td class="align-middle">
                                                <div class="d-inline-flex align-items-center gap-2">
                                                    <button type="button"
                                                        wire:click='decreaseQty({{ $item["product_id"] }})'
                                                        wire:loading.attr="disabled" class="btn btn-outline-main btn px-10">
                                                        &minus;
                                                    </button>

                                                    <span class="px-3 fw-semibold text-dark"
                                                        style="min-width: 32px; text-align: center;">
                                                        {{ $item['quantity'] }}
                                                    </span>

                                                    <button type="button"
                                                        wire:click='increaseQty({{ $item["product_id"] }})'
                                                        wire:loading.attr="disabled" class="btn btn-outline-main btn px-10">
                                                        &#43;
                                                    </button>
                                                </div>
                                            </td>


                                            <!-- Total Amount -->
                                            <td class="align-middle">
                                                <span
                                                    class="fw-semibold">${{ number_format($item['total_amount'], 2) }}</span>
                                            </td>
                                        </tr>

                                    @empty
                                        <td class="text-secondary">No items in cart</td>
                                    @endforelse

                                </tbody>
                            </table>
                        </div>

                        <div class="flex-between flex-wrap gap-16 mt-16">
                            <div class="flex-align gap-16">
                                <input wire:model.defer="coupon_code" type="text" class="common-input"
                                    placeholder="Coupon Code">
                                <button wire:click="applyCouponIfExists" type="button"
                                    class="btn btn-main py-18 w-100 rounded-8">Apply Coupon</button>
                            </div>
                        </div>
                        <div class="mr-0 mt-2">
                            @if(!is_null($coupon_error))
                                <small class="text-danger mt-2 d-block">{{ $coupon_error }}</small>
                            @elseif($discount > 0)
                                <small class="text-success mt-2 d-block">Coupon applied! Discount:
                                    ${{ number_format($discount, 2) }}</small>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-5">
                    <div class="cart-sidebar border border-gray-100 rounded-8 px-24 py-40">
                        <h6 class="text-xl mb-32">Shopping Summary</h6>
                        <div class="bg-color-three rounded-8 p-24">
                            <div class="mb-32 flex-between gap-8">
                                <span class="text-gray-900 font-heading-two">Subtotal</span>
                                <span class="text-gray-900 fw-semibold">${{number_format($grand_total, 0)}}</span>
                            </div>
                            <div class="mb-32 flex-between gap-8">
                                <span class="text-gray-900 font-heading-two">Extimated Delivery</span>
                                <span class="text-gray-900 fw-semibold">Free</span>
                            </div>
                            {{-- <div class="mb-0 flex-between gap-8">
                                <span class="text-gray-900 font-heading-two">Extimated Taxs</span>
                                <span class="text-gray-900 fw-semibold">USD 10.00</span>
                            </div> --}}
                            @if($discount)
                                <div class="mb-32 flex-between gap-8">
                                    <span class="text-gray-900 font-heading-two">Coupon Discount</span>
                                    <span class="text-success fw-semibold">- ${{ number_format($discount, 2) }}</span>
                                </div>
                            @endif
                        </div>


                        <div class="bg-color-three rounded-8 p-24 mt-24">
                            <div class="flex-between gap-8">
                                <span class="text-gray-900 text-xl fw-semibold">Total</span>
                                <span
                                    class="text-gray-900 text-xl fw-semibold">${{ number_format($grand_total - $discount, 2) }}</span>

                            </div>
                        </div>
                        <a href="{{url('/checkout')}}" wire:navigate
                            class="btn btn-main mt-40 py-18 w-100 rounded-8">Proceed to checkout</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="my-4 pb-10">
        @include('partials.home-page.discount')
    </div>


    <!-- ================================ Cart Section End ================================ -->

    <!-- ========================== Shipping Section Start ============================ -->
    <section class="shipping mb-24" id="shipping">
        <div class="container container-lg">
            <div class="row gy-4">
                <div class="col-xxl-3 col-sm-6" data-aos="zoom-in" data-aos-duration="400">
                    <div class="shipping-item flex-align gap-16 rounded-16 bg-main-50 hover-bg-main-100 transition-2">
                        <span
                            class="w-56 h-56 flex-center rounded-circle bg-main-600 text-white text-32 flex-shrink-0"><i
                                class="ph-fill ph-car-profile"></i></span>
                        <div class="">
                            <h6 class="mb-0">Free Shipping</h6>
                            <span class="text-sm text-heading">Free shipping all over the US</span>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-sm-6" data-aos="zoom-in" data-aos-duration="600">
                    <div class="shipping-item flex-align gap-16 rounded-16 bg-main-50 hover-bg-main-100 transition-2">
                        <span
                            class="w-56 h-56 flex-center rounded-circle bg-main-600 text-white text-32 flex-shrink-0"><i
                                class="ph-fill ph-hand-heart"></i></span>
                        <div class="">
                            <h6 class="mb-0"> 100% Satisfaction</h6>
                            <span class="text-sm text-heading">Free shipping all over the US</span>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-sm-6" data-aos="zoom-in" data-aos-duration="800">
                    <div class="shipping-item flex-align gap-16 rounded-16 bg-main-50 hover-bg-main-100 transition-2">
                        <span
                            class="w-56 h-56 flex-center rounded-circle bg-main-600 text-white text-32 flex-shrink-0"><i
                                class="ph-fill ph-credit-card"></i></span>
                        <div class="">
                            <h6 class="mb-0"> Secure Payments</h6>
                            <span class="text-sm text-heading">Free shipping all over the US</span>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-sm-6" data-aos="zoom-in" data-aos-duration="1000">
                    <div class="shipping-item flex-align gap-16 rounded-16 bg-main-50 hover-bg-main-100 transition-2">
                        <span
                            class="w-56 h-56 flex-center rounded-circle bg-main-600 text-white text-32 flex-shrink-0"><i
                                class="ph-fill ph-chats"></i></span>
                        <div class="">
                            <h6 class="mb-0"> 24/7 Support</h6>
                            <span class="text-sm text-heading">Free shipping all over the US</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ========================== Shipping Section End ============================ -->


</div>