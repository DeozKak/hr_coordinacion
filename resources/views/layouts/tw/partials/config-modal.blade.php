@php
    /* Mismas opciones y permisos que el modal de AdminLTE
       (vendor/adminlte/partials/navbar/menu-item-dropdown-user-menu.blade.php). */
    $opciones = collect([
        ['label' => 'Usuarios',              'url' => url('/admin/users'),                      'icon' => 'fa-user',            'tint' => 'blue',
         'ver' => fn () => auth()->user()->can('gestion_usuarios')],
        ['label' => 'Actividad Usuarios',    'url' => route('admin.users.activity.list'),       'icon' => 'fa-user-secret',     'tint' => 'violet',
         'ver' => fn () => auth()->user()->can('gestion_usuarios')],
        ['label' => 'Inspectores',           'url' => url('/inspectores'),                      'icon' => 'fa-helmet-safety',   'tint' => 'amber',
         'ver' => fn () => auth()->user()->can('gestion_inspectores')],
        ['label' => 'Cortes Producción',     'url' => url('/cortes_produccion'),                'icon' => 'fa-chart-column',    'tint' => 'emerald',
         'ver' => fn () => auth()->user()->canany(['ver_residente', 'ver_coordinacion_RP'])],
        ['label' => 'Parametrizar precios',  'url' => url('/fechasParametros'),                 'icon' => 'fa-coins',           'tint' => 'amber',
         'ver' => fn () => auth()->user()->can('reporte_produccion')],
        ['label' => 'Gestión Notificaciones','url' => url('/admin/notifications/manage'),       'icon' => 'fa-bell',            'tint' => 'rose',
         'ver' => fn () => auth()->user()->hasRole('admin')],
        ['label' => 'Sal. Mínimo - Aux. Transporte', 'url' => url('/nomina/parametrizarSalarioAux'), 'icon' => 'fa-money-bill-wave', 'tint' => 'emerald',
         'ver' => fn () => auth()->user()->can('gestion_nomina')],
        ['label' => 'Zonificación',          'url' => route('zonas.index'),                     'icon' => 'fa-map-location-dot', 'tint' => 'sky',
         'ver' => fn () => auth()->user()->canany(['ver_residente', 'ver_coordinacion_RP', 'ver_coordinacion_RN', 'ver_PQRS'])],
        ['label' => 'Causales de legalización', 'url' => route('causales.index'),               'icon' => 'fa-file-circle-check', 'tint' => 'violet',
         'ver' => fn () => auth()->user()->canany(['ver_residente', 'ver_coordinacion_RP'])],
    ])->filter(fn ($o) => ($o['ver'])())->values();
@endphp

@if ($opciones->isNotEmpty())
    <div x-data="{ abierto: false }" @abrir-config.window="abierto = true">
        <x-modal show="abierto" close="abierto = false" size="max-w-2xl"
                 icon="fa-gear" tint="slate" title="Configuración">
            <x-slot:subtitle>Accesos de administración</x-slot:subtitle>

            <div class="grid grid-cols-2 gap-3 p-4 2xl:p-5 sm:grid-cols-3">
                @foreach ($opciones as $o)
                    <a href="{{ $o['url'] }}"
                       class="group flex flex-col items-center gap-3 rounded-2xl border border-slate-200/80 p-4 2xl:p-5 text-center
                              transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md
                              dark:border-slate-700/60 dark:hover:border-brand-700">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl text-xl transition group-hover:scale-105 chip-{{ $o['tint'] }}">
                            <i class="fas {{ $o['icon'] }}"></i>
                        </span>
                        <span class="text-sm font-semibold leading-tight text-slate-700 dark:text-slate-200">
                            {{ $o['label'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </x-modal>
    </div>
@endif
