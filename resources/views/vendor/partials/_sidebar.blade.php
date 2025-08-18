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
                <a class="sidebar-link {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}"
                    href="{{ route('vendor.dashboard') }}"  @if(request()->routeIs('vendor.dashboard'))
                    aria-current="page" @endif>
                    <svg class="stroke-icon">
                        <use href="...#User"></use>
                    </svg>
                    <h6>Dashboard and analytics</h6>
                </a>
            </li>

            

            <!-- PRODUCT MANAGEMENT -->
            <li class="sidebar-main-title">
                <div>
                    <h5 class="f-w-700 sidebar-title pt-3">Product Catalog</h5>
                </div>
            </li>

            <li class="sidebar-list">
                <a class="sidebar-link {{ request()->routeIs('vendor.product-management') ? 'active' : '' }}"
                    href="" 
                    @if(request()->routeIs('vendor.product-management')) aria-current="page" @endif>
                    <svg class="stroke-icon">
                        <use href="...#Box"></use>
                    </svg>
                    <h6>Manage Vendors Products</h6>
                </a>
            </li>

            <!-- ORDER & PAYMENT -->
            <li class="sidebar-main-title">
                <div>
                    <h5 class="f-w-700 sidebar-title pt-3">Orders</h5>
                </div>
            </li>
            <li class="sidebar-list">
                <a class="sidebar-link {{ request()->routeIs('vendor.order-management') ? 'active' : '' }}"
                    href="" 
                    @if(request()->routeIs('vendor.order-management')) aria-current="page" @endif>
                    <svg class="stroke-icon">
                        <use href="...#Bag"></use>
                    </svg>
                    <h6>Orders</h6>
                </a>
            </li>

            <li class="sidebar-list">
                <a class="sidebar-link {{ request()->routeIs('vendor.products') ? 'active' : '' }}" href="{{route('vendor.products')}}" 
                    @if(request()->routeIs('vendor.roducts')) aria-current="page" @endif>
                    <svg class="stroke-icon">
                        <use href="...#Bag"></use>
                    </svg>
                    <h6>Products</h6>
                </a>
            </li>

            <!-- AUCTION SYSTEM -->
            <li class="sidebar-main-title">
                <div>
                    <h5 class="f-w-700 sidebar-title pt-3">Auctions</h5>
                </div>
            </li>
            <li class="sidebar-list">
                <a class="sidebar-link {{ request()->routeIs('vendor.auction-management') ? 'active' : '' }}"
                    href="" 
                    @if(request()->routeIs('vendor.auction-management')) aria-current="page" @endif>
                    <svg class="stroke-icon">
                        <use href=""></use>
                    </svg>
                    <h6>Manage Auctions</h6>
                </a>
            </li>

        </ul>
    </div>
    <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
</aside>