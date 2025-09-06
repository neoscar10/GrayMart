@php use Illuminate\Support\Facades\Storage; @endphp

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

                        {{-- Category Drill-Down (single path, no checkboxes) --}}
                        <div class="shop-sidebar__box bg-white border border-gray-100 rounded-8 p-32 mb-32 shadow-sm">
                            <div class="d-flex align-items-center justify-content-between mb-16">
                                <div class="d-flex align-items-center gap-8">
                                    @if($hasParent)
                                        <button class="btn btn-sm btn-outline-secondary" wire:click="goUpOne" title="Back">
                                            <i class="ph ph-arrow-left"></i>
                                        </button>
                                    @endif
                                    <h6 class="text-xl mb-0">
                                        {{ $currentCategory['name'] ?? 'Browse Categories' }}
                                    </h6>
                                </div>
                                <div class="d-flex gap-8">
                                    <button class="btn btn-sm btn-outline-secondary" wire:click="goToRoot"
                                        title="Top level">
                                        Top
                                    </button>
                                </div>
                            </div>

                            {{-- Breadcrumbs --}}
                            @if (!empty($breadcrumbs))
                                <div class="mb-12 text-sm">
                                    <a href="#" class="text-decoration-none" wire:click.prevent="goToRoot">All</a>
                                    @foreach ($breadcrumbs as $i => $crumb)
                                        <span class="mx-6 text-muted">/</span>
                                        @if ($i === count($breadcrumbs) - 1)
                                            <span class="fw-semibold">{{ $crumb['name'] }}</span>
                                        @else
                                            <a href="#" class="text-decoration-none"
                                                wire:click.prevent="selectCategory({{ $crumb['id'] }})">{{ $crumb['name'] }}</a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            {{-- Current Level List (click to drill deeper; products filter updates immediately) --}}
                            <ul class="list-unstyled m-0 p-0" id="gm-levelcats">
                                @forelse ($levelCategories as $cat)
                                    @php
                                        $img = $cat['image'] ? asset('storage/' . $cat['image']) : asset('assets/images/icons/folder.png');
                                        $kids = $childrenMap[$cat['id']] ?? [];
                                    @endphp
                                    <li class="mb-8" wire:key="lvl-{{ $cat['id'] }}">
                                        <a href="#" wire:click.prevent="selectCategory({{ $cat['id'] }})"
                                            class="d-flex align-items-center gap-10 text-decoration-none px-10 py-8 rounded-8 border border-transparent hover-border-main-200 hover-bg-main-50">
                                            <img src="{{ $img }}" alt="" class="rounded-4 border"
                                                style="width:18px;height:18px;object-fit:cover;">
                                            <span class="text-sm text-gray-900">{{ $cat['name'] }}</span>
                                            @if(!empty($kids))
                                                <span class="ms-auto text-muted"><i class="ph ph-caret-right"></i></span>
                                            @endif
                                        </a>
                                    </li>
                                @empty
                                    <li class="text-muted small">No subcategories here.</li>
                                @endforelse
                            </ul>
                        </div>

                        {{-- Price Filter --}}
                        <div class="shop-sidebar__box bg-white border border-gray-100 rounded-8 p-32 mb-32 shadow-sm">
                            <h6 class="text-xl border-bottom border-gray-100 pb-24 mb-24">Filter by Price</h6>
                            <div class="p-2">
                                <div class="text-lg fw-bold text-success-600 mb-2">
                                    Max Price: ${{ number_format($price_range, 0) }}
                                </div>
                                <input wire:model.live="price_range" type="range"
                                    class="w-100 h-2 mb-4 bg-success-100 rounded appearance-none cursor-pointer" min="0"
                                    max="{{ $priceMax }}" step="50">
                                <div class="d-flex justify-content-between text-sm text-gray-500">
                                    <span>$0</span>
                                    <span>${{ number_format($priceMax, 0) }}</span>
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
                                    class="w-44 h-44 flex-center border border-gray-100 rounded-6 text-2xl grid-btn disabled"
                                    disabled title="Grid disabled">
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

                                $basePrice = (float) ($product->p_eff_price ?? $product->price);
                                $dealPrice = (!is_null($product->buy_now_price) && $product->buy_now_price > 0 && $product->buy_now_price < $product->price)
                                    ? (float) $product->buy_now_price
                                    : null;

                                $isLimited = (bool) $product->is_reserved;

                                $hasVariants = ($product->variants_count ?? 0) > 0;
                                $vMin = $hasVariants ? (float) $product->v_min_price : null;
                                $vMax = $hasVariants ? (float) $product->v_max_price : null;
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
                                        <a href="{{ route('store.product', $product->slug) }}" class="link text-line-2"
                                           tabindex="0">
                                            {{ $product->name }}
                                        </a>
                                    </h6>

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

                                    {{-- PRICE ROW --}}
                                    @if ($isLimited)
                                        <div class="product-card__price my-20">
                                            <span class="text-heading text-md fw-semibold">Price: Highest bidder</span>
                                        </div>
                                    @else
                                        <div class="product-card__price my-20">
                                            @if ($hasVariants)
                                                @if ($vMin !== null && $vMax !== null)
                                                    @if (abs($vMax - $vMin) < 0.01)
                                                        <span class="text-heading text-md fw-semibold">
                                                            ${{ number_format($vMin, 2) }}
                                                            <span class="text-gray-500 fw-normal">/Qty</span>
                                                        </span>
                                                    @else
                                                        <span class="text-heading text-md fw-semibold">
                                                            ${{ number_format($vMin, 2) }} – ${{ number_format($vMax, 2) }}
                                                            <span class="text-gray-500 fw-normal">/Qty</span>
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-heading text-md fw-semibold">See options</span>
                                                @endif
                                            @else
                                                @if ($dealPrice)
                                                    <span class="text-gray-400 text-md fw-semibold text-decoration-line-through">
                                                        ${{ number_format($basePrice, 2) }}
                                                    </span>
                                                    <span class="text-heading text-md fw-semibold">
                                                        ${{ number_format($dealPrice, 2) }}
                                                        <span class="text-gray-500 fw-normal">/Qty</span>
                                                    </span>
                                                @else
                                                    <span class="text-heading text-md fw-semibold">
                                                        ${{ number_format($basePrice, 2) }}
                                                        <span class="text-gray-500 fw-normal">/Qty</span>
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                    @endif

                                    {{-- CTA --}}
                                    @if ($isLimited)
                                        @php
                                            // Find the current auction (prefer live over scheduled)
                                            $auction = $product->auctions()
                                                ->select('id','starts_at','ends_at','status')
                                                ->whereIn('status', ['live','scheduled'])
                                                ->orderByRaw("FIELD(status,'live','scheduled')") // live first
                                                ->orderBy('starts_at')
                                                ->first();
                                        @endphp

                                        @if($auction)
                                            <a href="{{ route('store.auctions.show', ['auction' => $auction->id]) }}"
                                               class="product-card__cart btn bg-gray-50 text-heading hover-bg-main-600 hover-text-white py-11 px-24 rounded-8 flex-center gap-8 fw-medium"
                                               tabindex="0">
                                                <span>Bid for Item <i class="fa-solid fa-money-check-dollar"></i></span>
                                            </a>
                                        @else
                                            <button class="product-card__cart btn bg-gray-50 text-muted py-11 px-24 rounded-8 flex-center gap-8 fw-medium"
                                                    disabled>
                                                <span>Auction Coming Soon</span>
                                            </button>
                                        @endif
                                    @else
                                        @if ($hasVariants)
                                            <a wire:click.prevent="openVariantPicker({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                               href="#"
                                               class="product-card__cart btn bg-gray-50 text-heading hover-bg-main-600 hover-text-white py-11 px-24 rounded-8 flex-center gap-8 fw-medium"
                                               tabindex="0">
                                                <span>Choose Options <i class="ph ph-squares-four"></i></span>
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
                            class="w-56 h-56 d-flex align-items-center justify-content-center rounded-circle bg-main-600 text-white text-32 flex-shrink-0">
                            <i class="ph-fill ph-car-profile"></i>
                        </span>
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
                            class="w-56 h-56 d-flex align-items-center justify-content-center rounded-circle bg-main-600 text-white text-32 flex-shrink-0">
                            <i class="ph-fill ph-hand-heart"></i>
                        </span>
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
                            class="w-56 h-56 d-flex align-items-center justify-content-center rounded-circle bg-main-600 text-white text-32 flex-shrink-0">
                            <i class="ph-fill ph-credit-card"></i>
                        </span>
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
                            class="w-56 h-56 d-flex align-items-center justify-content-center rounded-circle bg-main-600 text-white text-32 flex-shrink-0">
                            <i class="ph-fill ph-chats"></i>
                        </span>
                        <div>
                            <h6 class="mb-0">24/7 Support</h6>
                            <span class="text-sm text-heading">We’re here to help</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========== Variant Picker Small Modal ========== --}}
    @if ($showVariantModal)
        <div class="position-fixed top-0 start-0 w-100 h-100" style="background: rgba(0,0,0,0.45); z-index: 1050;"
             wire:click.self="closeVariantPicker" wire:keydown.escape="closeVariantPicker">
            <div class="bg-white rounded-12 shadow-lg p-20" style="max-width: 520px; width: 92%; margin: 10vh auto;">
                <div class="d-flex justify-content-between align-items-center mb-16">
                    <h6 class="mb-0">{{ $variantProductName ?? 'Choose Options' }}</h6>
                    <button type="button"
                            class="w-32 h-32 flex-center border border-gray-100 rounded-circle hover-bg-main-600 hover-text-white"
                            wire:click="closeVariantPicker">
                        <i class="ph ph-x"></i>
                    </button>
                </div>

                @if (count($variantOptions))
                    <div class="mb-16">
                        @foreach ($variantOptions as $opt)
                            <label
                                class="d-flex align-items-center justify-content-between border border-gray-100 rounded-8 px-12 py-10 mb-10 cursor-pointer">
                                <div class="d-flex align-items-center gap-10">
                                    <input type="radio" class="form-check-input" wire:model.live="selectedVariantId"
                                           value="{{ $opt['id'] }}">
                                    <span class="text-sm text-heading">{{ $opt['label'] }}</span>
                                </div>
                                <span class="text-sm fw-semibold">${{ number_format($opt['price'], 2) }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-warning mb-16">No variant options available.</div>
                @endif

                <div class="d-flex gap-8 justify-content-end">
                    <button type="button" class="btn btn-secondary hover-bg-gray-500 rounded-8"
                            wire:click="closeVariantPicker">Cancel</button>
                    <button type="button" class="btn bg-main-600 hover-bg-main-700 text-white rounded-8"
                            wire:click="confirmVariantAddToCart" wire:loading.attr="disabled" @disabled(!$selectedVariantId)>
                        <span wire:loading.remove wire:target="confirmVariantAddToCart">Add To Cart</span>
                        <span wire:loading wire:target="confirmVariantAddToCart">
                            <i class="fas fa-spinner fa-spin me-1"></i> Adding…
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
