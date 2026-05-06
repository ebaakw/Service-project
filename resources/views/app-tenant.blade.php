<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    @include('layouts.tenant-dashboard-head')
    @routes
    <x-translations />
    @viteReactRefresh
    @vite(['resources/css/tenant-app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="hold-transition layout-fixed sidebar-mini">
    @inertia
    @include('layouts.tenant-dashboard-footer')
    <script>
        document.body.addEventListener('click', function () {
            if (document.body.classList.contains('sidebar-open')) {
                document.body.classList.remove('sidebar-open');
            }
        });
    </script>
</body>
</html>
