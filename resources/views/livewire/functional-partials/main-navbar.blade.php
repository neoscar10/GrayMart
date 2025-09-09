<div>
    <!-- ==================== Header Start Here ==================== -->
    <header class="header bg-white border-bottom-0 box-shadow-3xl py-10 z-2">
        <div class="container container-lg">
            <nav class="header-inner d-flex justify-content-between gap-8 align-items-center">

                <div class="flex-align menu-category-wrapper position-relative" style="overflow: visible;">
                    <!-- Brand -->
                    <div class="logo me-12">
                        <a href="{{ url('/') }}" class="link text-decoration-none fw-bold" style="font-size:22px;">
                            Gray<span class="text-main-600">mart</span>
                        </a>
                    </div>

                    <!-- Category Button + Dropdown (UNCHANGED) -->
                    <div class="position-relative">
                        <button type="button" id="gm-cat-btn"
                            class="category-button d-flex align-items-center gap-12 text-white bg-success-600 px-20 py-12 rounded-6 hover-bg-success-700 transition-2"
                            aria-haspopup="true" aria-expanded="false">
                            <span class="text-xl line-height-1"><i class="ph ph-squares-four"></i></span>
                            <span>Browse Categories</span>
                            <span class="line-height-1 icon transition-2 ms-6"><i
                                    class="ph-bold ph-caret-down"></i></span>
                        </button>

                        <!-- Mega Dropdown (2 columns: Roots | Direct Children) -->
                        <div id="gm-cat-dd"
                            class="category-dropdown shadow bg-white border border-success-200 rounded-16 position-absolute inset-block-start-100 inset-inline-start-0 z-99"
                            style="min-width: 640px; display:none;">
                            <div class="d-flex">
                                <!-- Left: Root Categories -->
                                <div class="p-12 border-end"
                                    style="min-width: 280px; max-height: 420px; overflow:auto;">
                                    <ul class="list-unstyled m-0">
                                        @foreach($rootCategories as $root)
                                            @php
                                                $img = $root['image'] ? asset('storage/' . $root['image']) : asset('assets/images/icons/folder.png');
                                                $hasKids = !empty($childrenByParent[$root['id']] ?? []);
                                            @endphp
                                            <li class="mb-4" data-gm-parent="{{ $root['id'] }}">
                                                <a href="{{ url('/shop?selected_categories[0]=' . $root['id']) }}"
                                                    class="d-flex align-items-center justify-content-between text-decoration-none px-10 py-8 rounded-8 hover-bg-main-50">
                                                    <span class="d-flex align-items-center gap-10">
                                                        <img src="{{ $img }}" alt="" class="rounded-4 border"
                                                            style="width:20px;height:20px;object-fit:cover;">
                                                        <span
                                                            class="text-sm text-heading fw-medium">{{ $root['name'] }}</span>
                                                    </span>
                                                    @if($hasKids)
                                                        <i class="ph ph-caret-right text-gray-500"></i>
                                                    @endif
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                                <!-- Right: Next-Level Panel (one shown at a time) -->
                                <div class="flex-grow-1 p-12"
                                    style="min-width: 300px; max-width: 420px; max-height: 420px; overflow:auto;">
                                    @foreach($rootCategories as $root)
                                        @php $kids = $childrenByParent[$root['id']] ?? []; @endphp
                                        <div class="gm-child-panel" id="gm-child-{{ $root['id'] }}" style="display:none;">
                                            <div class="d-flex align-items-center justify-content-between mb-10">
                                                <h6 class="mb-0 text-sm text-heading fw-semibold">{{ $root['name'] }}</h6>
                                                <a href="{{ url('/shop?selected_categories[0]=' . $root['id']) }}"
                                                    class="text-sm">Open “{{ $root['name'] }}”</a>
                                            </div>

                                            @if(empty($kids))
                                                <div class="text-muted small">No subcategories.</div>
                                            @else
                                                <ul class="list-unstyled m-0">
                                                    @foreach($kids as $k)
                                                        @php
                                                            $kimg = $k['image'] ? asset('storage/' . $k['image']) : asset('assets/images/icons/folder.png');
                                                        @endphp
                                                        <li class="mb-2">
                                                            <a href="{{ url('/shop?selected_categories[0]=' . $k['id']) }}"
                                                                class="d-flex align-items-center gap-10 text-decoration-none px-8 py-8 rounded-8 hover-bg-main-50">
                                                                <img src="{{ $kimg }}" class="rounded-4 border"
                                                                    style="width:18px;height:18px;object-fit:cover;" alt="">
                                                                <span class="text-sm text-heading">{{ $k['name'] }}</span>
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    @endforeach

                                    <!-- Empty state (before hover) -->
                                    <div class="gm-child-empty text-muted small" id="gm-child-empty">Hover a category to
                                        see its items</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ==== Shops Button + Dropdown (MIRRORS CATEGORY BEHAVIOR) ==== --}}
                    <div class="position-relative ms-8" style="z-index: 1000;">
                        <button type="button" id="gm-shops-btn"
                            class="d-flex align-items-center gap-12 text-white bg-primary-600 px-18 py-12 rounded-6 hover-bg-primary-700 transition-2"
                            aria-haspopup="true" aria-expanded="false">
                            <span class="text-xl line-height-1"><i class="ph ph-storefront"></i></span>
                            <span>Shops</span>
                            <span class="line-height-1 icon transition-2 ms-6"><i
                                    class="ph-bold ph-caret-down"></i></span>
                        </button>

                        <div id="gm-shops-dd"
                            class="shadow bg-white border border-primary-200 rounded-16 position-absolute inset-block-start-100 inset-inline-start-0"
                            style="min-width: 380px; max-height: 460px; overflow:auto; display:none; z-index: 9999;">
                            <div class="p-12">
                                @if($vendors->isEmpty())
                                    <div class="text-muted small px-8 py-10">No shops available yet.</div>
                                @else
                                    <ul class="list-unstyled m-0">
                                        @foreach ($vendors as $v)
                                            @php
                                                $logo = $v->logo_url ?? asset('assets/images/thumbs/vendor-placeholder.png');
                                            @endphp
                                            <li class="mb-6">
                                                <a href="{{ route('store.vendor', $v->slug) }}"
                                                    class="d-flex align-items-center gap-12 text-decoration-none px-10 py-10 rounded-10 hover-bg-main-50 border border-transparent hover-border-main-200">
                                                    <img src="{{ $logo }}" alt="{{ $v->store_name }}"
                                                        class="rounded-circle border"
                                                        style="width:28px;height:28px;object-fit:cover;">
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="text-sm text-heading fw-semibold">{{ $v->store_name }}</span>
                                                        <span class="text-xs text-gray-500">Visit shop</span>
                                                    </div>
                                                    <i class="ph ph-caret-right ms-auto text-gray-500"></i>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Main Menu -->
                    <div class="header-menu d-lg-block d-none ms-12">
                        <ul class="nav-menu flex-align">
                            <li class="nav-menu__item">
                                <a href="{{ url('/') }}" class="nav-menu__link text-heading-two">HOME</a>
                            </li>
                            <li class="nav-menu__item">
                                <a href="{{ url('/shop') }}" class="nav-menu__link text-heading-two"
                                    wire:navigate>SHOP</a>
                            </li>
                            @if (Auth::user())
                                <li class="nav-menu__item">
                                    <a href="{{ route('account.orders') }}" class="nav-menu__link text-heading-two"
                                        wire:navigate>ORDERS</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- Right side: Auth + Cart -->
                <div class="header-right flex-align gap-20 px-4">
                    @if (Auth::user())
                        <form method="POST" action="{{ route('logout') }}" id="logout-form">
                            @csrf
                            <a href="#" class="text-dark text-decoration-none d-flex align-items-center"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="flex-align gap-4 item-hover">
                            <span class="text-xl text-gray-700 d-flex position-relative item-hover__text">
                                <i class="ph ph-user"></i>
                            </span>
                            <span class="text-md text-heading-three item-hover__text d-none d-lg-flex">Login</span>
                        </a>
                    @endif

                    <a href="{{ route('home.cart') }}" class="flex-align gap-4 item-hover">
                        <span class="text-xl text-gray-700 d-flex position-relative me-6 mt-6 item-hover__text">
                            <i class="ph ph-shopping-cart-simple"></i>
                            <span
                                class="w-16 h-16 flex-center rounded-circle bg-main-600 text-white text-xs position-absolute top-n6 end-n4">
                                {{ $total_count }}
                            </span>
                        </span>
                        <span class="text-md text-heading-three item-hover__text d-none d-lg-flex">Cart</span>
                    </a>

                    <button type="button" class="toggle-mobileMenu d-lg-none ms-3n text-gray-800 text-4xl d-flex">
                        <i class="ph ph-list"></i>
                    </button>
                </div>
            </nav>
        </div>
    </header>
    <!-- ==================== Header End Here ==================== -->

    {{-- Keep category JS AS-IS (UNCHANGED) --}}
    <script>
        (function () {
            const btn = document.getElementById('gm-cat-btn');
            const dd = document.getElementById('gm-cat-dd');
            const empty = document.getElementById('gm-child-empty');

            let openTimer = null;
            let closeTimer = null;

            function showDropdown() { dd.style.display = 'block'; }
            function hideDropdown() {
                dd.style.display = 'none';
                document.querySelectorAll('.gm-child-panel').forEach(el => el.style.display = 'none');
                if (empty) empty.style.display = 'block';
                document.querySelectorAll('[data-gm-parent].active').forEach(el => el.classList.remove('active'));
            }

            function showChildPanel(pid, li) {
                if (!pid) return;
                if (empty) empty.style.display = 'none';
                document.querySelectorAll('.gm-child-panel').forEach(el => el.style.display = 'none');
                const panel = document.getElementById('gm-child-' + pid);
                if (panel) panel.style.display = 'block';

                document.querySelectorAll('[data-gm-parent].active').forEach(x => x.classList.remove('active'));
                li.classList.add('active');
            }

            btn.addEventListener('mouseenter', () => { clearTimeout(closeTimer); showDropdown(); });
            btn.addEventListener('mouseleave', () => { closeTimer = setTimeout(hideDropdown, 200); });

            dd.addEventListener('mouseenter', () => clearTimeout(closeTimer));
            dd.addEventListener('mouseleave', () => { closeTimer = setTimeout(hideDropdown, 200); });

            document.querySelectorAll('[data-gm-parent]').forEach(li => {
                const pid = li.getAttribute('data-gm-parent');
                li.addEventListener('mouseenter', () => {
                    clearTimeout(openTimer);
                    openTimer = setTimeout(() => { showChildPanel(pid, li); }, 100);
                });
                li.addEventListener('mouseleave', () => clearTimeout(openTimer));
            });

            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hideDropdown(); });
        })();
    </script>

    {{-- Shops JS: IDENTICAL HOVER BEHAVIOR --}}
    <script>
        (function () {
            const sBtn = document.getElementById('gm-shops-btn');
            const sDD = document.getElementById('gm-shops-dd');
            if (!sBtn || !sDD) return;

            let sCloseTimer = null;

            function sOpen() { sDD.style.display = 'block'; }
            function sClose() { sDD.style.display = 'none'; }

            // Hover over the button shows dropdown (same timing as categories)
            sBtn.addEventListener('mouseenter', () => { clearTimeout(sCloseTimer); sOpen(); });
            sBtn.addEventListener('mouseleave', () => { sCloseTimer = setTimeout(sClose, 200); });

            // Hover over dropdown keeps it open
            sDD.addEventListener('mouseenter', () => clearTimeout(sCloseTimer));
            sDD.addEventListener('mouseleave', () => { sCloseTimer = setTimeout(sClose, 200); });

            // Close on Escape
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') sClose(); });
        })();
    </script>
</div>