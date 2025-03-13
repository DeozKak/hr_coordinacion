@php( $logout_url = View::getSection('logout_url') ?? config('adminlte.logout_url', 'logout') )
@php( $profile_url = View::getSection('profile_url') ?? config('adminlte.profile_url', 'profile') )
@php( $config_url = View::getSection('config_url') ?? config('adminlte.config_url', 'config') )

@if (config('adminlte.usermenu_profile_url', false))
@php( $profile_url = Auth::user()->adminlte_profile_url() )
@endif

@if (config('adminlte.use_route_url', false))
@php( $profile_url = $profile_url ? route($profile_url) : '' )
@php( $logout_url = $logout_url ? route($logout_url) : '' )
@php( $config_url = $config_url ? route($config_url) : '' )
@else
@php( $profile_url = $profile_url ? url($profile_url) : '' )
@php( $logout_url = $logout_url ? url($logout_url) : '' )
@php( $config_url = $config_url ? url($config_url) : '' )
@endif

<li class="nav-item dropdown user-menu">

    {{-- User menu toggler --}}
    <a href="#" class="nav-link" id="userMenuToggle">
        @if(config('adminlte.usermenu_image'))
            <img src="{{ Auth::user()->adminlte_image() }}" class="user-image img-circle elevation-2"
                alt="{{ Auth::user()->name }}">
        @endif
        <span @if(config('adminlte.usermenu_image')) class="d-none d-md-inline" @endif>
            {{ Auth::user()->name }}
        </span>
    </a>

    {{-- User menu dropdown --}}
    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right animate__animated" id="userMenuDropdown"
        style="border: none; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); border-radius: 10px; overflow: hidden;">

        {{-- User menu header --}}
        @if(!View::hasSection('usermenu_header') && config('adminlte.usermenu_header'))
            <li class="user-header {{ config('adminlte.usermenu_header_class', 'bg-primary') }}
                    @if(!config('adminlte.usermenu_image')) h-auto @endif">
                @if(config('adminlte.usermenu_image'))
                    <img src="{{ Auth::user()->adminlte_image() }}" class="img-circle elevation-2"
                        alt="{{ Auth::user()->name }}">
                @endif
                <p class="@if(!config('adminlte.usermenu_image')) mt-0 @endif">
                    {{ Auth::user()->name }}
                    @if(config('adminlte.usermenu_desc'))
                        <small>{{ Auth::user()->adminlte_desc() }}</small>
                    @endif
                </p>
            </li>
        @else
            @yield('usermenu_header')
        @endif

        {{-- User menu footer --}}
        <li class="user-footer d-flex flex-column align-items-start" style="background-color: #ffffff; padding: 10px; border-radius: 5px;">
            @if($profile_url)
                <a href="{{ $profile_url }}" class="btn btn-default btn-flat w-100 mb-1 text-left"
                    style="border: none; border-radius: 0; background-color: #ffffff; transition: background-color 0.3s;">
                    <i class="fa fa-user text-lightblue mr-2"></i> Perfil
                </a>
            @endif

            @if($config_url)
                <a href="#" class="btn btn-default btn-flat w-100 mb-1 text-left" data-toggle="modal"
                    data-target="#configModal" style="border: none; border-radius: 0; background-color: #ffffff; transition: background-color 0.3s;">
                    <i class="fa fa-cog text-secondary mr-2"></i> Configuración
                </a>
            @endif

            <a href="#" class="btn btn-default btn-flat w-100 text-left"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                style="border: none; border-radius: 0; background-color: #ffffff; transition: background-color 0.3s;">
                <i class="fa fa-sign-out-alt text-red mr-2"></i> Cerrar Sesión
            </a>
            <form id="logout-form" action="{{ $logout_url }}" method="POST" style="display: none;">
                @if(config('adminlte.logout_method'))
                    {{ method_field(config('adminlte.logout_method')) }}
                @endif
                {{ csrf_field() }}
            </form>
        </li>
    </ul>
    <style>
        #userMenuDropdown .btn:hover {
            background-color: #dceffe !important;
        }
    </style>
</li>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const userMenuToggle = document.getElementById('userMenuToggle');
        const userMenuDropdown = document.getElementById('userMenuDropdown');

        let isMenuOpen = false;

        userMenuToggle.addEventListener('click', function (event) {
            event.preventDefault();
            if (isMenuOpen) {
                userMenuDropdown.classList.remove('animate__fadeIn');
                userMenuDropdown.classList.add('animate__fadeOut');
                setTimeout(() => {
                    userMenuDropdown.classList.remove('show', 'animate__fadeOut');
                    isMenuOpen = false;
                }, 500);
            } else {
                userMenuDropdown.classList.add('show', 'animate__fadeIn');
                isMenuOpen = true;
            }
        });

        document.addEventListener('click', function (event) {
            if (!userMenuToggle.contains(event.target) && !userMenuDropdown.contains(event.target) && isMenuOpen) {
                userMenuDropdown.classList.remove('animate__fadeIn');
                userMenuDropdown.classList.add('animate__fadeOut');
                setTimeout(() => {
                    userMenuDropdown.classList.remove('show', 'animate__fadeOut');
                    isMenuOpen = false;
                }, 500);
            }
        });
    });
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        box-sizing: border-box;
    }

    .modal-header {
        background: #e0e0e0;
        text-align: center;
        padding: 22px;
        font-size: 24px;
        border-bottom: 1px solid #ccc;
    }

    .tabs {
        display: flex;
        border-bottom: 1px solid #ccc;
        background: #f3f3f3;
    }

    .tab {
        flex: 1;
        text-align: center;
        padding: 15px;
        cursor: pointer;
        color: #bbb;
        transition: color 0.3s;
    }

    .tab.active {
        color: #007BFF;
        border-bottom: 2px solid #007BFF;
        font-weight: bold;
    }

    .grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 33px;
        padding: 33px;
        justify-items: center;
        align-items: center;
    }

    .item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        cursor: pointer;
        width: 127%;
        height: 127%;
        transition: background-color 0.3s ease-in-out;
    }

    .item:hover {
        background-color: #f1f1f1;
    }

    .item-icon {
        width: 74px;
        height: 74px;
        border-radius: 50%;
        background: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        font-size: 32px;
        color: #0069d9;
    }

    .item-label {
        font-size: 14px;
        color: #333;
    }

    .custom-close-btn {
        background-color: #d3d3d3;
        border: none;
        color: #555;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 22px;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
        opacity: 1;
    }

    .custom-close-btn:hover {
        background-color: #bdbdbd;
        transform: scale(1.1);
    }

    .custom-close-btn span {
        line-height: 1;
    }
</style>

<!-- Modal de Configuración -->
<div class="modal fade" id="configModal" tabindex="-1" role="dialog" aria-labelledby="configModalLabel"
    aria-hidden="true" data-backdrop="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="configModalLabel" style="text-align: center; font-size: 20.5px; width: 100%; margin: 0 auto;">
                    Configuración</h5>
                <button type="button" class="close custom-close-btn" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="grid">
                @can('gestion_usuarios')
                <a href="{{ url('/admin/users') }}" class="item" target="_self">
                    <div class="item-icon"><i class="fas fa-user"></i></div>
                    <div class="item-label">Usuarios</div>
                </a>
                @endcan

                @can('gestion_inspectores')
                <a href="{{ url('/inspectores') }}" class="item" target="_self">
                    <div class="item-icon"><i class="fas fa-user-tie"></i></div>
                    <div class="item-label">Inspectores</div>
                </a>
                @endcan

                @canany(['ver_residente', 'ver_coordinacion_RP'])
                <a href="{{ url('/cortes_produccion') }}" class="item" target="_self">
                    <div class="item-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="item-label">Cortes Producción</div>
                </a>
                @endcan

                @can('reporte_produccion')
                <a href="{{ url('/fechasParametros') }}" class="item" target="_self">
                    <div class="item-icon"><i class="fas fa-coins"></i></div>
                    <div class="item-label">Parametrizar precios</div>
                </a>
                @endcan

                @role('admin')
                    <a href="{{ url('/admin/notifications/manage') }}" class="item" target="_self">
                        <div class="item-icon"><i class="fas fa-bell"></i></div>
                        <div class="item-label">Gestión Notificaciones</div>
                    </a>
                @endrole

                @canany(['ver_residente', 'ver_coordinacion_RP'])
                <a href="{{ url('/nomina/parametrizarSalarioAux') }}" class="item" target="_self">
                    <div class="item-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="item-label">Sal. Mínimo - Aux. Transporte</div>
                </a>
                @endcan
            </div>
        </div>
    </div>
</div>
