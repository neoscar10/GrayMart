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
                <a class="sidebar-link" href="javascript:void(0)">
                    <svg class="stroke-icon">
                        <use href="...#Home-dashboard"></use>
                    </svg>
                    <h6>Dashboard</h6><i class="iconly-Arrow-Right-2 icli"></i>
                </a>
                <ul class="sidebar-submenu">
                    <li><a href="#">Overview</a></li>
                    <li><a href="#">Vendor Analytics</a></li>
                    <li><a href="#">Sales Reports</a></li>
                </ul>
            </li>

            @if (Auth::user()->role == 'admin')
                <!-- USER & VENDOR MANAGEMENT -->
                <li class="sidebar-main-title">
                    <div>
                        <h5 class="f-w-700 sidebar-title pt-3">Users & Vendors</h5>
                    </div>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link" href="{{route('admin.user-management')}}">
                        <svg class="stroke-icon">
                            <use href="...#User"></use>
                        </svg>
                        <h6>All User Management</h6>
                    </a>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link" href="#">
                        <svg class="stroke-icon">
                            <use href="...#User"></use>
                        </svg>
                        <h6>Vendor Accounts</h6>
                    </a>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link" href="#">
                        <svg class="stroke-icon">
                            <use href="...#Shield-Done"></use>
                        </svg>
                        <h6>Roles & Permissions</h6>
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
                <a class="sidebar-link" href="{{route('admin.product-management')}}"><svg class="stroke-icon">
                        <use href="...#Box"></use>
                    </svg>
                    <h6>Manage Vendors Products</h6>
                </a>
            </li>
            

           
                <li class="sidebar-list">
                    <a class="sidebar-link" href="{{route('admin.category-management')}}"><svg class="stroke-icon">
                            <use href="...#Category"></use>
                        </svg>
                        <h6>Categories</h6>
                    </a>
                </li>

                <li class="sidebar-list">
                    <a class="sidebar-link" href=""><svg class="stroke-icon">
                            <use href=""></use>
                        </svg>
                        <h6>Manage Coupons</h6>
                    </a>
                </li>

                <li class="sidebar-list">
                    <a class="sidebar-link" href="#"><svg class="stroke-icon">
                            <use href=""></use>
                        </svg>
                        <h6>Sizes & Variants</h6>
                    </a>
                </li>

            


            <!-- ORDER & PAYMENT -->
            <li class="sidebar-main-title">
                <div>
                    <h5 class="f-w-700 sidebar-title pt-3">Orders & Payments</h5>
                </div>
            </li>
                <li class="sidebar-list">
                    <a class="sidebar-link" href="{" wire:navigate><svg class="stroke-icon">
                            <use href="...#Bag"></use>
                        </svg>
                        <h6>Orders</h6>
                    </a>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link" href="#"><svg class="stroke-icon">
                            <use href="...#Wallet"></use>
                        </svg>
                        <h6>Payments</h6>
                    </a>
                </li>
          



            <!-- AUCTION SYSTEM -->
            <li class="sidebar-main-title">
                <div>
                    <h5 class="f-w-700 sidebar-title pt-3">Auctions</h5>
                </div>
            </li>
            <li class="sidebar-list">
                <a class="sidebar-link" href="#"><svg class="stroke-icon">
                        <use href="...#Timer"></use>
                    </svg>
                    <h6>Active Auctions</h6>
                </a>
            </li>
            <li class="sidebar-list">
                <a class="sidebar-link" href="#"><svg class="stroke-icon">
                        <use href="...#Add-User"></use>
                    </svg>
                    <h6>Bidder History</h6>
                </a>
            </li>
            <li class="sidebar-list">
                <a class="sidebar-link" href="#"><svg class="stroke-icon">
                        <use href="...#Plus"></use>
                    </svg>
                    <h6>New Auction</h6>
                </a>
            </li>

           
                <!-- SYSTEM SETTINGS -->
                <li class="sidebar-main-title">
                    <div>
                        <h5 class="f-w-700 sidebar-title pt-3">System Settings</h5>
                    </div>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link" href="#"><svg class="stroke-icon">
                            <use href="...#Setting"></use>
                        </svg>
                        <h6>General Settings</h6>
                    </a>
                </li>
                <li class="sidebar-list">
                    <a class="sidebar-link" href="#"><svg class="stroke-icon">
                            <use href="...#Help"></use>
                        </svg>
                        <h6>Support Tickets</h6>
                    </a>
                </li>

           


        </ul>
    </div>
    <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
</aside>
  