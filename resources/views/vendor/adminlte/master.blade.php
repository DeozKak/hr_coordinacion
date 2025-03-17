<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    {{-- Base Meta Tags --}}
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Custom Meta Tags --}}
    @yield('meta_tags')

    {{-- Title --}}
    <title>
        @yield('title_prefix', config('adminlte.title_prefix', ''))
        @yield('title', config('adminlte.title', 'AdminLTE 3'))
        @yield('title_postfix', config('adminlte.title_postfix', ''))
    </title>

    {{-- Custom stylesheets (pre AdminLTE) --}}
    @yield('adminlte_css_pre')

    {{-- Base Stylesheets --}}
    @if(!config('adminlte.enabled_laravel_mix'))
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

        @if(config('adminlte.google_fonts.allowed', true))
            <link rel="stylesheet"
                href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
        @endif
    @else
        <link rel="stylesheet" href="{{ mix(config('adminlte.laravel_mix_css_path', 'css/app.css')) }}">
    @endif

    {{-- Extra Configured Plugins Stylesheets --}}
    @include('adminlte::plugins', ['type' => 'css'])

    {{-- Livewire Styles --}}
    @if(config('adminlte.livewire'))
        @if(intval(app()->version()) >= 7)
            @livewireStyles
        @else
            <livewire:styles />
        @endif
    @endif

    {{-- Custom Stylesheets (post AdminLTE) --}}
    @yield('adminlte_css')

    {{-- Favicon --}}
    @if(config('adminlte.use_ico_only'))
        <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}" />
    @elseif(config('adminlte.use_full_favicon'))
        <link rel="shortcut icon" href="{{ asset('favicons/favicon.ico') }}" />
        <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('favicons/apple-icon-57x57.png') }}">
        <link rel="apple-touch-icon" sizes="60x60" href="{{ asset('favicons/apple-icon-60x60.png') }}">
        <link rel="apple-touch-icon" sizes="72x72" href="{{ asset('favicons/apple-icon-72x72.png') }}">
        <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('favicons/apple-icon-76x76.png') }}">
        <link rel="apple-touch-icon" sizes="114x114" href="{{ asset('favicons/apple-icon-114x114.png') }}">
        <link rel="apple-touch-icon" sizes="120x120" href="{{ asset('favicons/apple-icon-120x120.png') }}">
        <link rel="apple-touch-icon" sizes="144x144" href="{{ asset('favicons/apple-icon-144x144.png') }}">
        <link rel="apple-touch-icon" sizes="152x152" href="{{ asset('favicons/apple-icon-152x152.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicons/apple-icon-180x180.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicons/favicon-16x16.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicons/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('favicons/favicon-96x96.png') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicons/android-icon-192x192.png') }}">
        <link rel="manifest" crossorigin="use-credentials" href="{{ asset('favicons/manifest.json') }}">
        <meta name="msapplication-TileColor" content="#ffffff">
        <meta name="msapplication-TileImage" content="{{ asset('favicon/ms-icon-144x144.png') }}">
    @endif

</head>

<body class="@yield('classes_body')" @yield('body_data')>

    {{-- Body Content --}}
    @yield('body')
    <input type="hidden" id="makeRead" name="makeRead" value="{{route('notifications.markAsRead')}}">
    {{-- Base Scripts --}}
    @if(!config('adminlte.enabled_laravel_mix'))
        <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
        <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
        <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('vendor/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
        <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
    @else
        <script src="{{ mix(config('adminlte.laravel_mix_js_path', 'js/app.js')) }}"></script>
    @endif

    {{-- Extra Configured Plugins Scripts --}}
    @include('adminlte::plugins', ['type' => 'js'])

    {{-- Livewire Script --}}
    @if(config('adminlte.livewire'))
        @if(intval(app()->version()) >= 7)
            @livewireScripts
        @else
            <livewire:scripts />
        @endif
    @endif

    {{-- Custom Scripts --}}
    @yield('adminlte_js')

    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: "Error",
                    text: "{{session('error')}}",
                    icon: "error"
                });
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(function () {
                let notificationId = 0;

                // Manejar clic en el ícono de eliminar
                $(document).on('click', '[id^="notificationTrash_"]', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    notificationId = this.id.split('_')[1];
                    marcarComoLeida(notificationId);
                });

                // Manejar clic en "Ver más"
                $(document).on('click', 'a.text-muted.text-sm', function (event) {
                    notificationId = $(this).closest('.dropdown-item').attr('id'); // Obtiene el ID de la notificación
                    if (notificationId) {
                        event.preventDefault(); // Evita la navegación inmediata
                        marcarComoLeida(notificationId, () => {
                            window.location.href = $(this).attr('href'); // Redirige después de eliminar
                        });
                    }
                });

            }, 800);

            function marcarComoLeida(notificationId, callback = null) {
                $.ajax({
                    url: "{{ route('notifications.markAsRead') }}",
                    type: "GET",
                    data: { notification_id: notificationId },
                    success: function (response) {
                        if (response.success) {
                            $('#' + notificationId).fadeOut(300, function () {
                                $(this).remove(); // Elimina la notificación del DOM
                            });

                            // Actualiza el contador de notificaciones
                            let badge = $('.navbar-badge');
                            let count = parseInt(badge.text()) || 0;
                            if (count > 1) {
                                badge.text(count - 1);
                            } else {
                                badge.text('');
                            }

                            // Ejecuta el callback si existe (redirigir al enlace)
                            if (typeof callback === "function") {
                                callback();
                            }
                        } else {
                            console.error("Error:", response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            }

            const makeRead = document.getElementById('makeRead').value;
            setTimeout(() => {
                document.addEventListener('click', function (event) {
                    let dropdownLink = null;
                    if (event.target.matches('a.deleteNotification')) {
                        dropdownLink = event.target;
                    }

                    if (dropdownLink) {
                        const notificationId = dropdownLink.id;

                        const requestUrl = `${makeRead}?notification_id=${notificationId}`;

                        $.ajax({
                            url: requestUrl,
                            type: 'GET',
                            success: function (response) {

                            },
                            error: function (xhr, status, error) {

                            }
                        });
                    }
                });
            }, 10);

            $(document).ready(function () {
                $('body').on('shown.bs.dropdown', '.nav-item.dropdown', function () {
                    $.ajax({
                        url: "{{ route('notifications.markAllAsRead') }}",
                        type: "GET",
                        success: function (response) {
                            if (response.success) {
                                $('.navbar-badge').text(''); // Ocultar el número de notificaciones
                            } else {
                                console.error("Error:", response.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error(xhr.responseText);
                        }
                    });
                });
            });

            $(document).on('click', '.adminlte-dropdown-content', function (event) {
                event.stopPropagation();
            });
        });
    </script>
</body>

</html>
