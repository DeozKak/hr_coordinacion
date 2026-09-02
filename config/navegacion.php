<?php

/*
|--------------------------------------------------------------------------
| Navegación
|--------------------------------------------------------------------------
|
| Marca y menú lateral de la aplicación.
|
| Este archivo nació como config/adminlte.php: era la configuración publicada
| del paquete jeroennoten/laravel-adminlte y siguió alimentando el menú
| después de migrar la interfaz a Tailwind. Al retirar el paquete se conservó
| aquí sólo lo que la aplicación usa de verdad, con el nombre que le
| corresponde y sin los cientos de ajustes del paquete ni sus entradas de
| ejemplo comentadas.
|
| El menú lo recorre layouts/tw/partials/sidebar.blade.php y lo pinta
| menu-item.blade.php, que respeta 'can', los submenús y los encabezados de
| sección, y descarta los antiguos widgets de barra superior ('type' o
| 'topnav_right'), que ya no existen.
|
*/

return [

    /* Título del navegador y del pie de página. */
    'titulo' => 'Seg Operacion EYC',

    /* Adónde lleva pulsar la marca. */
    'inicio' => 'home',

    /* Logotipo de la empresa, relativo a public/. */
    'logo' => 'favicon.ico',
    'logo_alt' => 'E&C Ingeniería',

    'menu' => [
        // Navbar items:
        [
            'type' => 'navbar-search',
            'text' => 'buscar',
            'topnav_right' => true,
        ],
        [
            'type' => 'fullscreen-widget',
            'topnav_right' => true,
        ],


        [
            'type' => 'navbar-notification',
            'id' => 'my-notification',
            'icon' => 'fas fa-bell',
            'route' => 'notifications.index',
            'topnav_right' => true,
            'dropdown_mode' => true,
            'dropdown_flabel' => 'todas las notificaciones',
            'update_cfg' => [
                'url' => 'notifications/get',
                'period' => 60,
            ],
        ],


        // Sidebar items:
        [
            'type' => 'sidebar-menu-search',
            'text' => 'buscar',
        ],
        [
            'text' => 'blog',
            'url' => 'admin/blog',
            'can' => 'manage-blog',
        ],
        /* ------------------------------------------------------------------
           Revisiones Periódicas: oculto del menú a petición del equipo.
           De sus 13 entradas, 8 nunca llegaron a tener ruta (Orden RP,
           Listado impresión, Orden Cert, Res. Pend, Res. Asig, Orca, Nómina
           e Indicadores) y las 5 restantes son de un módulo que no se
           terminó.

           Solo se retira del sidebar: las rutas siguen registradas y nadie
           pierde permisos, así que restaurarlo es quitar este comentario.
           ------------------------------------------------------------------ */
        /*
        ['header' => 'Resvisiones Periódicas', 'can' => 'ver_coordinacion_RP',],
        [
            'text' => 'Cargues',
            'icon' => 'fas fa-upload',
            'can' => 'cargue_tareas',
            'submenu' => [
                [
                    'text' => 'Asignadas y Cerradas',
                    'url' => 'load',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],

            ],
        ],

        [
            'text' => 'Gestión',
            'icon' => 'fas fa-tasks',
            'can' => 'ver_coordinacion_RP',
            'submenu' => [
                [
                    'text' => 'Coordinación',
                    'url' => '/gestion/coordinacion',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Planilla',
                    'url' => '/gestion/planilla',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Orden RP',
                    'url' => 'orden_rp',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Listado impresión',
                    'url' => 'listado_impresion',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Orden Cert',
                    'url' => 'orden_cert',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],

            ],
        ],
        [
            'text' => 'Seguimiento',
            'icon' => 'fas fa-walking',
            'can' => 'ver_coordinacion_RP',
            'submenu' => [
                [
                    'text' => 'App',
                    'url' => '/gestion/aplicacion',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Res. Pend',
                    'url' => 'res_pend',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Res. Asig',
                    'url' => 'res_asig',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Historico',
                    'url' => '/seguimiento/historico',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Orca',
                    'url' => 'orca',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Nómina',
                    'url' => 'nomina',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Indicadores',
                    'url' => 'indicadores',
                    'icon' => 'far fa-circle',
                    'label_color' => 'success',
                ],
            ],
        ],
        */


        ['header' => 'Bitacoras', 'can' => ['ver_bitacoras', 'generar_bitacoras']],


        [
            'text' => 'Bitacoras',
            'icon' => 'fas fa-file-alt',
            'can' => ['ver_bitacoras', 'generar_bitacoras'],
            'submenu' => [
                [
                    'text' => 'Generar',
                    'url' => 'bitacora',
                    'icon' => 'far fa-circle',
                    'can' => 'generar_bitacoras',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Ver Reportes',
                    'url' => 'bitacora/reportes',
                    'icon' => 'far fa-circle',
                    'can' => 'ver_bitacoras',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Devoluciones',
                    'url' => 'bitacora/devoluciones',
                    'icon' => 'far fa-circle',
                    'can' => 'ver_bitacoras',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Control Stikers',
                    'url' => 'bitacora/stickers',
                    'icon' => 'far fa-circle',
                    'can' => 'generar_bitacoras',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Contratos sin categoria',
                    'url' => 'bitacora/contratos_sin_categoria',
                    'icon' => 'far fa-circle',
                    'can' => 'generar_bitacoras',
                    'label_color' => 'success',
                ],
            ]
        ],

         ['header' => 'PQRS', 'can' => ['ver_PQRS','coordinacion_pqrs'],],


         [
             'text' => 'PQRS',
             'can' => ['ver_PQRS','coordinacion_pqrs'],
             'icon' => 'fas fa-phone',
             'submenu' => [

                 [
                     'text' => 'Estadísticas',
                     'url' => 'pqrs/coordinacion/estadisticas',
                     'icon' => 'far fa-circle',
                     'can' => ['ver_PQRS','coordinacion_pqrs'],
                     'label_color' => 'success',
                 ],
                 [
                     'text' => 'Coordinación',
                     'url' => 'pqrs/coordinacion',
                     'icon' => 'far fa-circle',
                     'can' => ['ver_PQRS','coordinacion_pqrs'],
                     'label_color' => 'success',
                 ],

             ],

        //         [
        //             'text' => 'Cortes Producción',
        //             'url' => 'cortes_produccion',
        //             'icon' => 'far fa-circle',
        //             'can' => ['ver_residente', 'ver_coordinacion_RP'],
        //             'label_color' => 'success',

        //         ],
        //         [
        //             'text' => 'Parametrizar precios',
        //             'url' => 'fechasParametros',
        //             'icon' => 'far fa-circle',
        //             'can' => ['reporte_produccion'],
        //             'label_color' => 'success',

        //         ],
        //         [
        //             'text' => 'Sal.Minimo - Aux.Transporte',
        //             'url' => 'nomina/parametrizarSalarioAux',
        //             'icon' => 'far fa-circle',
        //             'can' => ['ver_residente', 'ver_coordinacion_RP'],
        //             'label_color' => 'success',
        //         ],
        //     ]
        ],

        ['header' => 'Supervisión Producción', 'can' => ['ver_residente', 'ver_produccion'],],


        [
            'text' => 'Producción',
            'can' => ['ver_residente', 'ver_produccion'],
            'icon' => 'fas fa-hammer',
            'submenu' => [
                [
                    'text' => 'Ver Producción',
                    'url' => '/produccion',
                    'icon' => 'far fa-circle',
                    'can' => 'ver_residente',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Detalles Producción',
                    'url' => '/produccion/detalles',
                    'icon' => 'fas fa-circle',
                    'can' => ['ver_residente', 'ver_produccion'],
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Detalles Fallidas',
                    'url' => '/produccion/fallidas',
                    'icon' => 'fas fa-circle',
                    'can' => ['ver_residente', 'ver_produccion'],
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Reporte de producción diario',
                    'url' => '/reporteProduccion',
                    'icon' => 'far fa-circle',
                    'can' => 'reporte_produccion',
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Reporte consolidado',
                    'url' => '/produccion/ReporteConsolidado',
                    'icon' => 'far fa-circle',
                    'can' => 'reporte_produccion',
                    'label_color' => 'success',
                ],

            ]
        ],

        /*   ['header' => 'Programación', 'can' => ['generar_programacion','ver_programacion'],],
   */
        [
            'text' => 'Programación',
            'can' => ['generar_programacion', 'ver_programacion'],
            'icon' => 'fas fa-calendar-minus',
            'submenu' => [
                [
                    'text' => 'Programar',
                    'url' => '/programacion',
                    'icon' => 'far fa-circle',
                    'can' => ['generar_programacion', 'ver_programacion'],
                    'label_color' => 'success',
                ],
                [
                    'text' => 'Ver Programación',
                    'url' => '/programacion/detalles',
                    'icon' => 'far fa-circle',
                    'can' => ['ver_programacion'],
                    'label_color' => 'success',
                ],

            ]
        ],

        ['header' => 'Nomina', 'can' => ['gestion_nomina'],],

        [
            'text' => 'Nomina',
            'can' => ['gestion_nomina'],
            'icon' => 'fas fa-money-check-alt',
            'submenu' => [
                [
                    'text' => 'Nomina',
                    'url' => '/nomina/reporteNomina',
                    'icon' => 'far fa-circle',
                    'can' => 'gestion_nomina',
                    'label_color' => 'success',
                ]
            ]
        ],

        ['header' => 'SST', 'can' => ['gestion_preoperacional'],],

        [
            'text' => 'Preoperacional',
            'url' => '/preoperacional',
            'icon' => 'fas fa-motorcycle',
            'can' => 'gestion_preoperacional',
            'label_color' => 'success',
        ],
    ],
];
