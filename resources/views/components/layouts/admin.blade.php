<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Graymart Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Rubik:400,500,600" rel="stylesheet">

    {{-- Alpine --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>


    <!-- CSS -->
    @include('admin.partials.css')

    @livewireStyles
</head>

<body>

    <div class="page-wrapper compact-wrapper" id="pageWrapper">
        <!-- Header -->
        @include('admin.partials._header')

        <div class="page-body-wrapper">
            <!-- Sidebar -->
            @include('admin.partials._sidebar')

            <!-- Main Content -->
            <div class="page-body">
                <div class="container-fluid">
                    {{ $slot }}
                </div>
            </div>

            <!-- Footer -->
            @include('admin.partials._footer')
        </div>
    </div>

    <!-- JS -->
   @include('admin.partials._js')

    

    @livewireScripts
    @stack('scripts')
    <script src="//unpkg.com/alpinejs" defer></script>

</body>

</html>