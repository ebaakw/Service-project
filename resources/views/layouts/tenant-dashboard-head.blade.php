<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<link rel="stylesheet" href="{{ asset('dashboard/plugins/fontawesome-free/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('dashboard/plugins/ionicons/css/ionicons.min.css') }}">
@if (app()->getLocale() === 'ar')
    <link rel="stylesheet" href="{{ asset('dashboard/dist/css/adminlte-rtl.css') }}">
    <style>
        .custom-switch .custom-control-label::after {
            right: calc(-1rem + 2px) !important;
        }
        #nprogress .spinner {
            left: 5px !important;
            top: 18px !important;
            right: unset !important;
        }
    </style>
@else
    <link rel="stylesheet" href="{{ asset('dashboard/dist/css/adminlte.css') }}">
@endif
