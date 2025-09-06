<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <base href="{{ url('/') }}/">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Graymart' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">

    @include('partials.store-pages.css')


</head>

<body>


    @include('partials.store-pages.topheader')
    {{-- @include('partials.store-pages.middleheader') --}}
    {{-- @include('partials.store-pages.mainheader') --}}
    @livewire('functional-partials.main-navbar')



    {{ $slot }}

    {{-- @livewireScripts --}}

    @include('partials.store-pages.footer')
    @include('partials.store-pages.js')



    {{--
    <script src="{{ asset('vendor/livewire/livewire.js') }}"></script> --}}

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>