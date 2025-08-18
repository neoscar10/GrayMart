<div>
    {{-- =============================== Shop Section Start ======================================== --}}
    <section class="shop py-80">
        <div class="container container-lg">
            <div class="row">

                {{-- Sidebar Start --}}
                <div class="col-lg-3">
                    <div class="shop-sidebar position-relative">
                        <button type="button"
                            class="shop-sidebar__close d-lg-none d-flex w-32 h-32 flex-center border border-gray-100 rounded-circle hover-bg-main-600 position-absolute inset-inline-end-0 me-10 mt-8 hover-text-white hover-border-main-600">
                            <i class="ph ph-x"></i>
                        </button>

                        {{-- Category Filter (Parents only) --}}
                        <div class="shop-sidebar__box bg-white border border-gray-100 rounded-8 p-32 mb-32 shadow-sm">
                            <h6 class="text-xl border-bottom border-gray-100 pb-24 mb-24">Product Category</h6>
                            <ul class="max-h-540 overflow-y-auto scroll-sm">
                                @forelse ($categories as $category)
                                    <li class="mb-24" wire:key="cat-{{ $category->id }}">
                                        <input wire:model.live="selected_categories" type="checkbox"
                                            id="cat-{{ $category->id }}" value="{{ $category->id }}">
                                        <label for="cat-{{ $category->id }}"
                                            class="ms-2 text-gray-900 hover-text-main-600 mb-0">
                                            {{ $category->name }}
                                        </label>
                                    </li>
                                @empty
                                    <li class="text-muted">No categories yet.</li>
                                @endforelse
                            </ul>
                        </div>

                        {{-- Price Filter (dynamic max) --}}
                        <div class="shop-sidebar__box bg-white border border-gray-100 rounded-8 p-32 mb-32 shadow-sm">
                            <h6 class="text-xl border-bottom border-gray-100 pb-24 mb-24">Filter by Price</h6>
                            <div class="p-2">
                                <div class="text-lg fw-bold text-success-600 mb-2">
                                    Max Price: ₦{{ number_format($price_range, 0) }}
                                </div>
                                <input wire:model.live="price_range" type="range"
                                    class="w-100 h-2 mb-4 bg-success-100 rounded appearance-none cursor-pointer" min="0"
                                    max="{{ $priceMax }}" step="50">
                                <div class="d-flex justify-content-between text-sm text-gray-500">
                                    <span>₦0</span>
                                    <span>₦{{ number_format($priceMax, 0) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="shop-sidebar__box rounded-8">
                            <img src="{{ asset('assets/images/thumbs/advertise-img1.png') }}" alt=""
                                style="height: 510px">
                        </div>
                    </div>
                </div>
                {{-- Sidebar End --}}

                {{-- Content Start --}}
                <div class="col-lg-9">
                    {{-- Top Start --}}
                    <div class="flex-between gap-16 flex-wrap mb-40">
                        {{-- Live Search --}}
                        <div class="mb-4 w-75">
                            <input type="text" wire:model.debounce.300ms="search" placeholder="Search products..."
                                class="form-control px-16 py-12 border border-gray-200 rounded-8 shadow-sm w-100" />
                        </div>

                        <div class="position-relative d-flex align-items-center gap-16 flex-wrap">
                            <div class="list-grid-btns d-flex align-items-center gap-16">
                                <button type="button"
                                    class="w-44 h-44 flex-center border border-gray-100 rounded-6 text-2xl list-btn">
                                    <i class="ph-bold ph-list-dashes"></i>
                                </button>
                                <button type="button"
                                    class="w-44 h-44 flex-center border border-main-600 text-white bg-main-600 rounded-6 text-2xl grid-btn">
                                    <i class="ph ph-squares-four"></i>
                                </button>
                            </div>
                            <div class="position-relative text-gray-500 d-flex align-items-center gap-4 text-14">
                                <label for="sorting" class="text-inherit flex-shrink-0 mb-0">Sort by:</label>
                                <select wire:model.live="sort"
                                    class="form-control common-input px-14 py-14 text-inherit rounded-6 w-auto"
                                    id="sorting">
                                    <option value="latest">Latest</option>
                                    <option value="price">Price (Low → High)</option>
                                </select>
                            </div>
                            <button type="button"
                                class="w-44 h-44 d-lg-none d-flex flex-center border border-gray-100 rounded-6 text-2xl sidebar-btn">
                                <i class="ph-bold ph-funnel"></i>
                            </button>
                        </div>
                    </div>
                    {{-- Top End --}}

                    {{-- Products --}}
                    <div class="list-grid-wrapper">
                        @forelse ($products as $product)
                                                                            @php
                            $img = (is_array($product->images) && count($product->images))
                                ? Storage::url($product->images[0])
                                : asset('assets/images/thumbs/product-placeholder.png');

                            $basePrice = (float) $product->price;
                            $dealPrice = (!is_null($product->buy_now_price) && $product->buy_now_price > 0 && $product->buy_now_price < $product->price)
                                ? (float) $product->buy_now_price
                                : null;

                            $isLimited = (bool) $product->is_reserved;
                                                                            @endphp

                                                                            <div class="product-card h-100 p-16 border border-gray-100 hover-border-main-600 rounded-16 position-relative transition-2"
                                                                                wire:key="p-{{ $product->id }}">
                                                                                <a href="{{ route('store.product', $product->slug) }}"
                                                                                    class="product-card__thumb flex-center rounded-8 bg-gray-50 position-relative">
                                                                                    <img src="{{ $img }}" alt="{{ $product->name }}" width="100" height="130"
                                                                                        class="object-fit-cover rounded-8" />
                                                                                    @if ($isLimited)
                                                                                        <span
                                                                                            class="product-card__badge bg-primary-600 px-8 py-4 text-sm text-white position-absolute inset-inline-start-0 inset-block-start-0">
                                                                                            Limited
                                                                                        </span>
                                                                                    @endif
                                                                                </a>

                                                                                <div class="product-card__content mt-16 w-100">
                                                                                    <h6 class="title text-lg fw-semibold mt-12 mb-8">
                                                                                        <a href="{{ route('store.product', $product->slug) }}" class="link text-line-2" tabindex="0">
                                                                                            {{ $product->name }}
                                                                                        </a>
                                                                                    </h6>

                                                                                    {{-- RATING + SHOP NAME (same row, space-between) --}}
                                                                                    @php
                                                                                        $avg = $product->reviews_avg_rating ? round($product->reviews_avg_rating, 1) : null;
                                                                                        $count = (int) ($product->reviews_count ?? 0);
                                                                                        $shop = $product->store_name ?? '—';
                                                                                    @endphp

                                                                                    <div class="flex-between mb-12 mt-16 gap-6">
                                                                                        <div class="flex-align gap-6">
                                                                                            <span class="text-xs fw-medium text-gray-500">
                                                                                                {{ $avg !== null ? number_format($avg, 1) : 'New' }}
                                                                                            </span>
                                                                                            <span class="text-xs fw-medium text-warning-600 d-flex">
                                                                                                <i class="ph-fill ph-star"></i>
                                                                                            </span>
                                                                                            <span class="text-xs fw-medium text-gray-500">
                                                                                                ({{ number_format($count) }})
                                                                                            </span>
                                                                                        </div>

                                                                                        <span class="text-xs fw-medium text-gray-600 text-truncate" title="{{ $shop }}">
                                                                                            {{ $shop }}
                                                                                        </span>
                                                                                    </div>


                                                                                    {{-- PRICE ROW (separate from rating) --}}
                                                                                    @if ($isLimited)
                                                                                        <div class="product-card__price my-20">
                                                                                            <span class="text-heading text-md fw-semibold">Price: Highest bidder</span>
                                                                                        </div>
                                                                                    @else
                                                                                        <div class="product-card__price my-20">
                                                                                            @if ($dealPrice)
                                                                                                <span class="text-gray-400 text-md fw-semibold text-decoration-line-through">
                                                                                                    ₦{{ number_format($basePrice, 2) }}
                                                                                                </span>
                                                                                                <span class="text-heading text-md fw-semibold">
                                                                                                    ₦{{ number_format($dealPrice, 2) }}
                                                                                                    <span class="text-gray-500 fw-normal">/Qty</span>
                                                                                                </span>
                                                                                            @else
                                                                                                <span class="text-heading text-md fw-semibold">
                                                                                                    ₦{{ number_format($basePrice, 2) }}
                                                                                                    <span class="text-gray-500 fw-normal">/Qty</span>
                                                                                                </span>


                                                                                            @endif
                                                                                        </div>
                                                                                    @endif


                                                                                    @if ($isLimited)
                                                                                        <a href="{{ route('store.product', $product->slug) }}"
                                                                                            class="product-card__cart btn bg-gray-50 text-heading hover-bg-main-600 hover-text-white py-11 px-24 rounded-8 flex-center gap-8 fw-medium"
                                                                                            tabindex="0">
                                                                                            <span>Bid for Item <i class="fa-solid fa-money-check-dollar"></i></span>
                                                                                        </a>
                                                                                    @else
                                                                                        <a wire:click.prevent="addToCart({{ $product->id }})" href="#"
                                                                                            class="product-card__cart btn bg-gray-50 text-heading hover-bg-main-600 hover-text-white py-11 px-24 rounded-8 flex-center gap-8 fw-medium"
                                                                                            tabindex="0">
                                                                                            <span wire:loading.remove wire:target="addToCart({{ $product->id }})">
                                                                                                Add To Cart <i class="ph ph-shopping-cart"></i>
                                                                                            </span>
                                                                                            <span wire:loading wire:target="addToCart({{ $product->id }})">
                                                                                                <i class="fas fa-spinner fa-spin me-1"></i> Adding…
                                                                                            </span>
                                                                                        </a>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                        @empty
                            <div class="alert alert-warning">No products match your filters.</div>
                        @endforelse
                    </div>


                    {{-- Pagination --}}
                    <div class="mt-5">
                        @if (View::exists('vendor.pagination.custom-shop'))
                            {{ $products->links('vendor.pagination.custom-shop') }}
                        @else
                            {{ $products->links() }}
                        @endif
                    </div>
                </div>
                {{-- Content End --}}

            </div>
        </div>
    </section>
    {{-- =============================== Shop Section End ======================================== --}}

    {{-- ========================== Shipping Section (static) ============================ --}}
    <section class="shipping mb-24" id="shipping">
        <div class="container container-lg">
            <div class="row gy-4">
                <div class="col-xxl-3 col-sm-6" data-aos="zoom-in" data-aos-duration="400">
                    <div
                        class="shipping-item d-flex align-items-center gap-16 rounded-16 bg-main-50 hover-bg-main-100 transition-2">
                        <span
                            class="w-56 h-56 d-flex align-items-center justify-content-center rounded-circle bg-main-600 text-white text-32 flex-shrink-0"><i
                                class="ph-fill ph-car-profile"></i></span>
                        <div>
                            <h6 class="mb-0">Free Shipping</h6>
                            <span class="text-sm text-heading">Across Nigeria</span>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-sm-6" data-aos="zoom-in" data-aos-duration="600">
                    <div
                        class="shipping-item d-flex align-items-center gap-16 rounded-16 bg-main-50 hover-bg-main-100 transition-2">
                        <span
                            class="w-56 h-56 d-flex align-items-center justify-content-center rounded-circle bg-main-600 text-white text-32 flex-shrink-0"><i
                                class="ph-fill ph-hand-heart"></i></span>
                        <div>
                            <h6 class="mb-0">100% Satisfaction</h6>
                            <span class="text-sm text-heading">Easy returns</span>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-sm-6" data-aos="zoom-in" data-aos-duration="800">
                    <div
                        class="shipping-item d-flex align-items-center gap-16 rounded-16 bg-main-50 hover-bg-main-100 transition-2">
                        <span
                            class="w-56 h-56 d-flex align-items-center justify-content-center rounded-circle bg-main-600 text-white text-32 flex-shrink-0"><i
                                class="ph-fill ph-credit-card"></i></span>
                        <div>
                            <h6 class="mb-0">Secure Payments</h6>
                            <span class="text-sm text-heading">PCI-compliant</span>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-sm-6" data-aos="zoom-in" data-aos-duration="1000">
                    <div
                        class="shipping-item d-flex align-items-center gap-16 rounded-16 bg-main-50 hover-bg-main-100 transition-2">
                        <span
                            class="w-56 h-56 d-flex align-items-center justify-content-center rounded-circle bg-main-600 text-white text-32 flex-shrink-0"><i
                                class="ph-fill ph-chats"></i></span>
                        <div>
                            <h6 class="mb-0">24/7 Support</h6>
                            <span class="text-sm text-heading">We’re here to help</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>