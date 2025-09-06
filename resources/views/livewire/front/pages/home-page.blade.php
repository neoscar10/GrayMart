<div>
    <div class="preloader">
        <img src="{{ asset('assets/images/icon/preloader.gif') }}" alt="">
    </div>

    {{-- Keep your static partials if they don’t rely on the old project’s DB --}}
    @includeIf('partials.home-page.banner')
    @includeIf('partials.home-page.discount')

    {{-- ================= Categories (Parents only) ================= --}}
<section class="py-30">
    <div class="container container-lg">
        <div class="section-heading mb-2 py-2">
            <h5 class="mb-0 pt-2">Shop by Category</h5>
        </div>

        <div class="product-one-slider g-12 pt-4">
            @forelse($parentCategories as $cat)
                @php
                    $catImg = $cat->image ? Storage::url($cat->image) : asset('assets/images/thumbs/placeholder-cat.png');
                @endphp

                <div data-aos="fade-up">
                    <a href="{{ route('store.shop', ['category' => $cat->slug, 'selected_categories' => [$cat->id]]) }}"
                        class="text-decoration-none text-reset d-block">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <!-- Square, no-crop image box -->
                            <div
                                class="ratio ratio-1x1 bg-light rounded-3 overflow-hidden d-flex align-items-center justify-content-center">
                                <img src="{{ $catImg }}" alt="{{ $cat->name }}" class="w-100 h-100 object-fit-contain p-2"
                                    loading="lazy">
                            </div>

                            <div class="card-body p-2 text-center">
                                <div class="fw-semibold small text-truncate">{{ $cat->name }}</div>
                                <div class="d-flex justify-content-center mt-1">
                                    <span class="badge rounded-pill bg-light text-secondary border">{{ __('Shop') }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="alert alert-warning">No products to show yet.</div>
            @endforelse
        </div>
    </div>
</section>



    {{-- ================= Flash Sales (Products) ================= --}}
    <div class="product pt-60">
        <div class="container container-lg">
            <div class="section-heading">
                <div class="flex-between flex-wrap gap-8">
                    <h5 class="mb-0 wow fadeInLeft">Flash Sales Today</h5>
                    <div class="flex-align gap-16 wow fadeInRight">
                        <a href="{{ route('store.shop') }}"
                            class="text-sm fw-medium text-gray-700 hover-text-main-600 hover-text-decoration-underline">View
                            All Deals</a>
                        <div class="flex-align gap-8">
                            <button type="button" id="product-one-prev"
                                class="slick-prev slick-arrow flex-center rounded-circle border border-gray-100 hover-border-main-600 text-xl hover-bg-main-600 hover-text-white transition-1">
                                <i class="ph ph-caret-left"></i>
                            </button>
                            <button type="button" id="product-one-next"
                                class="slick-next slick-arrow flex-center rounded-circle border border-gray-100 hover-border-main-600 text-xl hover-bg-main-600 hover-text-white transition-1">
                                <i class="ph ph-caret-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-one-slider g-12">
                @forelse($flashProducts as $p)
                    @php
    $img = is_array($p->images) && count($p->images) ? Storage::url($p->images[0]) : asset('assets/images/thumbs/product-placeholder.png');
    // If you have true discounts, adjust this logic. Here: show buy_now_price if lower.
    $basePrice = $p->price;
    $dealPrice = (!is_null($p->buy_now_price) && $p->buy_now_price > 0 && $p->buy_now_price < $p->price) ? $p->buy_now_price : null;
    $rating = round($p->reviews_avg_rating ?? 0, 1);
    $reviewsCount = $p->reviews_count ?? 0;
                      @endphp

                    <div class="" data-aos="fade-up">
                        <div
                            class="product-card px-20 py-16 border border-gray-100 hover-border-main-600 rounded-16 position-relative transition-2">
                            <button type="button" wire:click="addToCart({{ $p->id }})"
                                class="product-card__cart btn bg-main-50 text-main-600 hover-bg-main-600 hover-text-white py-11 px-24 rounded-pill flex-align gap-8 position-absolute inset-block-start-0 inset-inline-end-0 me-16 mt-16">
                                Add <i class="ph ph-shopping-cart"></i>
                            </button>

                            <a href="{{ route('store.product', $p->slug) }}"
                                class="product-card__thumb flex-center overflow-hidden">
                                <img src="{{ $img }}" alt="{{ $p->name }}">
                            </a>

                            <div class="product-card__content mt-12">
                                <div class="product-card__price mb-8 d-flex align-items-center gap-8">
                                    @if($dealPrice)
                                        <span class="text-heading text-md fw-semibold">
                                            ₦{{ number_format($dealPrice, 2) }} <span
                                                class="text-gray-500 fw-normal">/Qty</span>
                                        </span>
                                        <span class="text-gray-400 text-md fw-semibold text-decoration-line-through">
                                            ₦{{ number_format($basePrice, 2) }}
                                        </span>
                                    @else
                                        <span class="text-heading text-md fw-semibold">
                                            ₦{{ number_format($basePrice, 2) }} <span
                                                class="text-gray-500 fw-normal">/Qty</span>
                                        </span>
                                    @endif
                                </div>

                                <div class="flex-align gap-6">
                                    <span class="text-xs fw-bold text-gray-600">{{ $rating }}</span>
                                    <span class="text-15 fw-bold text-warning-600 d-flex"><i
                                            class="ph-fill ph-star"></i></span>
                                    <span class="text-xs fw-bold text-gray-600">({{ $reviewsCount }})</span>
                                </div>

                                <h6 class="title text-lg fw-semibold mt-12 mb-20">
                                    <a href="{{ route('store.product', $p->slug) }}"
                                        class="link text-line-2">{{ $p->name }}</a>
                                </h6>

                                {{-- Stock/progress omitted: your schema doesn’t expose sold/stock. --}}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-warning">No products to show yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ================= Flash sales banners (left static as template art) ================= --}}
    <section class="flash-sales pt-80 overflow-hidden">
        <div class="container container-lg">
            <div class="row gy-4 arrow-style-two">
                <div class="col-lg-6" data-aos="fade-up">
                    <div
                        class="flash-sales-item rounded-16 overflow-hidden z-1 position-relative flex-align flex-0 justify-content-between gap-8 ps-56-px">
                        <img src="{{ asset('assets/images/bg/flash-sale-bg1.png') }}" alt=""
                            class="position-absolute inset-block-start-0 inset-inline-start-0 w-100 h-100 object-fit-cover z-n1 flash-sales-item__bg">
                        <div class="flash-sales-item__content ms-sm-auto">
                            <h6 class="text-32 mb-8">X-Connect Smart Television</h6>
                            <p class="text-neutral-500 mb-12">Time remaining until the end of the offer.</p>
                            <div class="countdown" id="countdown1">
                                <ul class="countdown-list flex-align flex-wrap">
                                    <li
                                        class="countdown-list__item py-8 px-12 text-heading flex-align gap-4 text-sm fw-medium box-shadow-4xl rounded-5">
                                        <span class="days"></span> D</li>
                                    <li
                                        class="countdown-list__item py-8 px-12 text-heading flex-align gap-4 text-sm fw-medium box-shadow-4xl rounded-5">
                                        <span class="hours"></span> H</li>
                                    <li
                                        class="countdown-list__item py-8 px-12 text-heading flex-align gap-4 text-sm fw-medium box-shadow-4xl rounded-5">
                                        <span class="minutes"></span> M</li>
                                    <li
                                        class="countdown-list__item py-8 px-12 text-heading flex-align gap-4 text-sm fw-medium box-shadow-4xl rounded-5">
                                        <span class="seconds"></span> S</li>
                                </ul>
                            </div>
                            <a href="{{ route('store.shop') }}"
                                class="btn btn-main d-inline-flex align-items-center rounded-pill gap-8 mt-24">
                                Shop Now <span class="icon text-xl d-flex"><i class="ph ph-arrow-right"></i></span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6" data-aos="fade-up">
                    <div
                        class="flash-sales-item rounded-16 overflow-hidden z-1 position-relative flex-align flex-0 justify-content-between gap-8 ps-56-px">
                        <img src="{{ asset('assets/images/bg/flash-sale-bg2.png') }}" alt=""
                            class="position-absolute inset-block-start-0 inset-inline-start-0 w-100 h-100 object-fit-cover z-n1 flash-sales-item__bg">
                        <div class="flash-sales-item__content">
                            <h6 class="text-32 mb-8">Vegetables Combo Box</h6>
                            <p class="text-heading mb-12">Time remaining until the end of the offer.</p>
                            <div class="countdown" id="countdown2">
                                <ul class="countdown-list flex-align flex-wrap">
                                    <li
                                        class="countdown-list__item py-8 px-12 flex-align gap-4 text-sm fw-medium box-shadow-4xl rounded-5 bg-main-600 text-white">
                                        <span class="days"></span> D</li>
                                    <li
                                        class="countdown-list__item py-8 px-12 flex-align gap-4 text-sm fw-medium box-shadow-4xl rounded-5 bg-main-600 text-white">
                                        <span class="hours"></span> H</li>
                                    <li
                                        class="countdown-list__item py-8 px-12 flex-align gap-4 text-sm fw-medium box-shadow-4xl rounded-5 bg-main-600 text-white">
                                        <span class="minutes"></span> M</li>
                                    <li
                                        class="countdown-list__item py-8 px-12 flex-align gap-4 text-sm fw-medium box-shadow-4xl rounded-5 bg-main-600 text-white">
                                        <span class="seconds"></span> S</li>
                                </ul>
                            </div>
                            <a href="{{ route('store.shop') }}"
                                class="btn bg-success-600 hover-bg-success-700 d-inline-flex align-items-center rounded-pill gap-8 mt-24 text-white">
                                Shop Now <span class="icon text-xl d-flex"><i class="ph ph-arrow-right"></i></span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ================= Offer (left static) ================= --}}
    @includeIf('partials.home-page.offer')

    {{-- ================= Brands (Vendor logos) ================= --}}
    <div class="brand py-80 overflow-hidden">
        <div class="container container-lg">
            <div class="brand-inner p-24 rounded-16">
                <div class="section-heading">
                    <div class="flex-between flex-wrap gap-8">
                        <h5 class="mb-0 wow fadeInLeft">Some of our notable brands</h5>
                        <div class="flex-align gap-16 wow fadeInRight">
                            <a href="{{ route('store.shop') }}"
                                class="text-sm fw-medium text-gray-700 hover-text-main-600 hover-text-decoration-underline">View
                                All</a>
                            <div class="flex-align gap-8">
                                <button type="button" id="brand-prev"
                                    class="slick-prev slick-arrow flex-center rounded-circle border border-gray-100 hover-border-main-600 text-xl hover-bg-main-600 hover-text-white transition-1">
                                    <i class="ph ph-caret-left"></i>
                                </button>
                                <button type="button" id="brand-next"
                                    class="slick-next slick-arrow flex-center rounded-circle border border-gray-100 hover-border-main-600 text-xl hover-bg-main-600 hover-text-white transition-1">
                                    <i class="ph ph-caret-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="brand-slider arrow-style-two">
                    @forelse($brands as $b)
                        <div class="brand-item" data-aos="zoom-in">
                            <img src="{{ asset('storage/' . $b->logo_path) }}" alt="{{ $b->store_name ?? 'Brand' }}">
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No brands yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Newsletter (static) ================= --}}
    @includeIf('partials.home-page.newsletter')
</div>