<style>
    .custom-menu {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        min-width: 150px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
        z-index: 1000;
    }

    .custom-menu.show {
        display: block;
    }

    .cursor-pointer {
        cursor: pointer;
    }
</style>

<header class="page-header row">
    <!-- Left Logo -->
    <div class="logo-wrapper d-flex align-items-center col-auto">
        <a href="{{ route('admin.dashboard') }}">
            <img class="dark-logo img-fluid" src="{{ asset('admin_assets/images/logo/logo-dark.png') }}" alt="logo">
        </a>

       
        <a class="close-btn toggle-sidebar ms-2" href="javascript:void(0)" aria-label="Toggle sidebar">
           
            <i class="fa-solid fa-bars fa-lg"></i>
        </a>
    </div>

    <!-- Header Content -->
    <div class="page-main-header col d-flex justify-content-between align-items-center">
        <!-- Left Text -->
        <div class="header-left">
            <div class="logo">
                <a href="{{ route('admin.dashboard') }}" class="logo-link">
                    <span class="logo-text">Graymart</span>
                    @if(Auth::check())
                        <span class="logo-text">{{ Auth::user()->role }}</span>
                    @endif
                </a>
            </div>
        </div>

        <!-- Right Profile Dropdown -->
        <div class="nav-right">
            <ul class="header-right mb-0 d-flex align-items-center">
                {{-- Bell Icon --}}
                <li><livewire:notification-bell /></li>

                <li class="profile-nav custom-dropdown position-relative">
                    <div class="user-wrap cursor-pointer">
                        <div class="user-img">
                            <img src="{{ asset('admin_assets/images/profile.png') }}" alt="user">
                        </div>
                        <div class="user-content">
                            @if(Auth::check())
                                <h6>{{ Auth::user()->name }}</h6>
                                <p class="mb-0">
                                    {{ Auth::user()->role }}
                                    <i class="fa-solid fa-chevron-down"></i>
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="custom-menu overflow-hidden">
                        <ul class="profile-body list-unstyled m-0 p-2">
                            <li class="py-1">
                                <a href="{{ route('profile.show') }}"
                                    class="text-dark text-decoration-none d-flex align-items-center">
                                    <i class="fas fa-user me-2"></i> Profile
                                </a>
                            </li>
                            <li class="py-1">
                                <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                    @csrf
                                    <a href="#" class="text-dark text-decoration-none d-flex align-items-center"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </a>
                                </form>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Profile dropdown (unchanged)
        const profileNav = document.querySelector('.profile-nav');
        const customMenu = profileNav?.querySelector('.custom-menu');
        if (profileNav && customMenu) {
            profileNav.addEventListener('click', function (event) {
                event.stopPropagation();
                customMenu.classList.toggle('show');
            });
            document.addEventListener('click', function () {
                customMenu.classList.remove('show');
            });
        }

        // Sidebar icon toggle (keeps your existing sidebar behavior)
        const sidebarToggle = document.querySelector('.toggle-sidebar');
        const icon = sidebarToggle?.querySelector('i');

        if (sidebarToggle && icon) {
            // On click: just swap the icon class; your existing sidebar JS still runs
            sidebarToggle.addEventListener('click', function () {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-xmark'); // close icon
            });
        }
    });
</script>