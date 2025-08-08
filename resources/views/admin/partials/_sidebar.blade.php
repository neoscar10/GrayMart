<aside class="page-sidebar">
    <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
    <div class="main-sidebar" id="main-sidebar">
        <ul class="sidebar-menu" id="simple-bar">

            <!-- GENERAL -->
            <li class="sidebar-main-title">
                <div>
                    <h5 class="f-w-700 sidebar-title">General</h5>
                </div>
            </li>
            <li class="sidebar-list">
                <a class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                    href="{{ route('admin.dashboard') }}" wire:navigate @if(request()->routeIs('admin.dashboard'))
                    aria-current="page" @endif>
                    <svg class="stroke-icon">
                        <use href="...#User"></use>
                    </svg>
                    <h6>Dashboard and analytics</h6>
                </a>
            </li>

            @if (Auth::user()->role == 'admin')
                <!-- USER & VENDOR MANAGEMENT -->
                <li class="sidebar-main-title">
                    <div>
                        <h5 class="f-w-700 sidebar-title pt-3">User accounts</h5>
                    </div>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link {{ request()->routeIs('admin.user-management') ? 'active' : '' }}"
                        href="{{ route('admin.user-management') }}" wire:navigate
                        @if(request()->routeIs('admin.user-management')) aria-current="page" @endif>
                        <svg class="stroke-icon">
                            <use href="...#User"></use>
                        </svg>
                        <h6>All User Management</h6>
                    </a>
                </li>

                <li class="sidebar-list">
                    <a class="sidebar-link {{ request()->routeIs('admin.view-vendors') ? 'active' : '' }}"
                        href="{{ route('admin.view-vendors') }}" wire:navigate @if(request()->routeIs('admin.view-vendors'))
                        aria-current="page" @endif>
                        <svg class="stroke-icon">
                            <use href="...#User"></use>
                        </svg>
                        <h6>Vendors</h6>
                    </a>
                </li>
            @endif

            <!-- PRODUCT MANAGEMENT -->
            <li class="sidebar-main-title">
                <div>
                    <h5 class="f-w-700 sidebar-title pt-3">Product Catalog</h5>
                </div>
            </li>

            <li class="sidebar-list">
                <a class="sidebar-link {{ request()->routeIs('admin.product-management') ? 'active' : '' }}"
                    href="{{ route('admin.product-management') }}" wire:navigate
                    @if(request()->routeIs('admin.product-management')) aria-current="page" @endif>
                    <svg class="stroke-icon">
                        <use href="...#Box"></use>
                    </svg>
                    <h6>Manage Vendors Products</h6>
                </a>
            </li>

            <li class="sidebar-list">
                <a class="sidebar-link {{ request()->routeIs('admin.category-management') ? 'active' : '' }}"
                    href="{{ route('admin.category-management') }}" wire:navigate
                    @if(request()->routeIs('admin.category-management')) aria-current="page" @endif>
                    <svg class="stroke-icon">
                        <use href="...#Category"></use>
                    </svg>
                    <h6>Categories</h6>
                </a>
            </li>

            <li class="sidebar-list">
                <a class="sidebar-link {{ request()->routeIs('admin.manage-variants') ? 'active' : '' }}"
                    href="{{ route('admin.manage-variants') }}" wire:navigate
                    @if(request()->routeIs('admin.manage-variants')) aria-current="page" @endif>
                    <svg class="stroke-icon">
                        <use href=""></use>
                    </svg>
                    <h6>Sizes & Variants</h6>
                </a>
            </li>

            <!-- ORDER & PAYMENT -->
            <li class="sidebar-main-title">
                <div>
                    <h5 class="f-w-700 sidebar-title pt-3">Orders</h5>
                </div>
            </li>
            <li class="sidebar-list">
                <a class="sidebar-link {{ request()->routeIs('admin.order-management') ? 'active' : '' }}"
                    href="{{ route('admin.order-management') }}" wire:navigate
                    @if(request()->routeIs('admin.order-management')) aria-current="page" @endif>
                    <svg class="stroke-icon">
                        <use href="...#Bag"></use>
                    </svg>
                    <h6>Orders</h6>
                </a>
            </li>

            <!-- AUCTION SYSTEM -->
            <li class="sidebar-main-title">
                <div>
                    <h5 class="f-w-700 sidebar-title pt-3">Auctions</h5>
                </div>
            </li>
            <li class="sidebar-list">
                <a class="sidebar-link {{ request()->routeIs('admin.auction-management') ? 'active' : '' }}"
                    href="{{ route('admin.auction-management') }}" wire:navigate
                    @if(request()->routeIs('admin.auction-management')) aria-current="page" @endif>
                    <svg class="stroke-icon">
                        <use href=""></use>
                    </svg>
                    <h6>Manage Auctions</h6>
                </a>
            </li>

            <!-- SYSTEM SETTINGS / MODERATION -->
            <li class="sidebar-main-title">
                <div>
                    <h5 class="f-w-700 sidebar-title pt-3">Moderation</h5>
                </div>
            </li>
            <li class="sidebar-list">
                <a class="sidebar-link {{ request()->routeIs('admin.reviews') ? 'active' : '' }}"
                    href="{{ route('admin.reviews') }}" wire:navigate @if(request()->routeIs('admin.reviews'))
                    aria-current="page" @endif>
                    <svg class="stroke-icon">
                        <use href=""></use>
                    </svg>
                    <h6>Review Moderation</h6>
                </a>
            </li>

        </ul>
    </div>
    <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
</aside>