<div>
    <!-- ==================== Header Start Here ==================== -->
    <header class="header bg-white border-bottom-0 box-shadow-3xl py-10 z-2">
        <div class="container container-lg">
            <nav class="header-inner d-flex justify-content-between gap-8">
                <div class="flex-align menu-category-wrapper position-relative">

                    <!-- Category Dropdown Start -->
                    <div class="logo">
                        <a href="{{ url('/') }}" class="link">
                            Gray<span>mart</span>
                        </a>
                    </div>
                    <div class="">
                        <button type="button"
                            class="category-button d-flex align-items-center gap-12 text-white bg-success-600 px-20 py-16 rounded-6 hover-bg-success-700 transition-2">
                            <span class="text-xl line-height-1"><i class="ph ph-squares-four"></i></span>
                            <span class="">Browse Categories</span>
                            <span class="line-height-1 icon transition-2"><i class="ph-bold ph-caret-down"></i></span>
                        </button>

                        <!-- Dropdown Start -->
                        <div
                            class="category-dropdown border border-success-200 shadow bg-white p-16 rounded-16 w-100 max-w-472 position-absolute inset-block-start-100 inset-inline-start-0 z-99 transition-2">
                            <div class="d-grid grid-cols-3-repeat gap-4 max-h-350 overflow-y-auto">
                                @foreach ($categories as $category)
                                    <a href="/shop?selected_categories[0]={{$category->id}}"
                                        class="py-16 px-8 rounded-8 hover-bg-main-50 d-flex flex-column align-items-center text-center border border-white hover-border-main-100">
                                        <span class="">
                                            <img src="{{asset('storage/' . $category->image)}}" alt="Icon" class="w-40">
                                        </span>
                                        <span class="fw-semibold text-heading mt-16 text-sm">{{$category->name}}</span>
                                    </a>
                                @endforeach

                            </div>
                        </div>
                        <!-- Dropdown End -->

                    </div>
                    <!-- Category Dropdown End -->

                    <!-- Menu Start  -->
                    <div class="header-menu d-lg-block d-none">
                        <!-- Nav Menu Start -->
                        <ul class="nav-menu flex-align ">

                            <li class="nav-menu__item">
                                <a href="{{url('/')}}" class="nav-menu__link text-heading-two">HOME</a>
                            </li>
                            <li class="nav-menu__item">
                                <a href="{{url('/shop')}}" class="nav-menu__link text-heading-two"
                                    wire:navigate>SHOP</a>
                            </li>

                        </ul>

                    </div>

                </div>
                <!-- form location Start -->



                <!-- form location start -->

                <!-- Header Right start -->
                <div class="header-right flex-align gap-20 px-4">


                    <a href="javascript:void(0)" class="flex-align gap-4 item-hover">
                        <span class="text-xl text-gray-700 d-flex position-relative item-hover__text">
                            <i class="ph ph-user"></i>
                        </span>
                        <span class="text-md text-heading-three item-hover__text d-none d-lg-flex">Profile</span>
                    </a>

                    <a href="{{route('home.cart')}}" class="flex-align gap-4 item-hover">
                        <span class="text-xl text-gray-700 d-flex position-relative me-6 mt-6 item-hover__text">
                            <i class="ph ph-shopping-cart-simple"></i>
                            <span
                                class="w-16 h-16 flex-center rounded-circle bg-main-600 text-white text-xs position-absolute top-n6 end-n4">{{$total_count}}</span>
                        </span>
                        <span class="text-md text-heading-three item-hover__text d-none d-lg-flex">Cart</span>
                    </a>

                    <button type="button" class="toggle-mobileMenu d-lg-none ms-3n text-gray-800 text-4xl d-flex"> <i
                            class="ph ph-list"></i> </button>
                </div>
                <!-- Header Right End  -->
            </nav>
        </div>
    </header>
    <!-- ==================== Header End Here ==================== -->
</div>