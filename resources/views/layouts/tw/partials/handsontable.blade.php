{{-- Handsontable, declarado en un solo sitio.
     Úsalo con: @include('layouts.tw.partials.handsontable')
     y pon class="ht-theme-main" en el contenedor de la grilla.

     Dos cosas que hay que respetar:
     · Desde la v15 el CSS vive en styles/ (no en dist/) y se divide en base + tema.
       La versión se replica en config/adminlte.php; manténlas iguales.
     · La arroba va DENTRO de la interpolación: `handsontable@{{ $v }}` dispararía
       el escape @{{ }} de Blade y saldría el literal. --}}
@once
    @php $hotBase = 'https://cdn.jsdelivr.net/npm/handsontable@18.0.0'; @endphp

    @push('styles')
        <link rel="stylesheet" href="{{ $hotBase }}/styles/handsontable.min.css">
        <link rel="stylesheet" href="{{ $hotBase }}/styles/ht-theme-main.min.css">

        {{-- Este <style> va DESPUÉS de los <link> a propósito: nuestras reglas
             tienen la misma especificidad que las del tema (.ht-theme-main), así
             que sólo ganan si cargan después. Antes vivían en app.css, que se
             carga antes, y el tema las pisaba. --}}
        <style>
            /* Los valores se declaran explícitos por modo en vez de con light-dark():
               así no dependen de cómo resuelva `color-scheme` el navegador. */
            .ht-theme-main {
                --ht-font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, sans-serif;

                /* En px a propósito. HOT mide la altura de fila haciendo
                   Math.ceil(parseFloat(valor)) sobre el valor CRUDO de la variable
                   (getComputedStyle no resuelve las custom properties), así que
                   "1.25rem" se convertía en 2px y ".5rem" en 1px: filas de ~4px.
                   La grilla principal lo disimulaba porque el contenido la estira,
                   pero el desplegable se quedaba en una rendija con solo la barra.

                   Densidad: el tema trae 4px/8px de padding y 20px de interlineado
                   (fila de 29px). Poner 8px verticales lo subía a 37px y en
                   portátiles dejaba de caber. Se vuelve a la densidad del tema con
                   la fuente un punto más baja: fila de 27px. */
                --ht-font-size: 12px;
                --ht-line-height: 18px;
                --ht-cell-horizontal-padding: 8px;
                --ht-cell-vertical-padding: 4px;
                --ht-border-radius: 8px;

                --ht-background-color: #ffffff;
                --ht-background-secondary-color: #f8fafc;
                --ht-foreground-color: #334155;
                --ht-foreground-secondary-color: #64748b;
                --ht-border-color: #e2e8f0;
                --ht-cell-horizontal-border-color: #f1f5f9;
                --ht-cell-vertical-border-color: #f1f5f9;
                --ht-cell-read-only-background-color: #ffffff;
                /* handsontable.css aplica `color: var(--ht-read-only-color) !important`
                   a toda celda readOnly. Como la grilla entera lo es, atenuarlas
                   dejaba el texto sin contraste: se iguala al color normal. */
                --ht-read-only-color: #334155;
                --ht-disabled-color: #94a3b8;

                --ht-header-background-color: #f8fafc;
                --ht-header-foreground-color: #475569;
                --ht-header-font-weight: 600;
                --ht-header-row-background-color: #f8fafc;
                --ht-header-row-foreground-color: #64748b;
                --ht-header-highlighted-background-color: #eef4ff;
                --ht-header-highlighted-foreground-color: #1a37b5;
                --ht-header-active-background-color: #eef4ff;
                --ht-header-active-foreground-color: #1a37b5;

                --ht-accent-color: #1f47e0;
                --ht-cell-selection-background-color: rgba(31, 71, 224, .08);
                --ht-cell-selection-border-color: #1f47e0;

                --ht-checkbox-border-radius: 4px;
                --ht-checkbox-background-color: #ffffff;
                --ht-checkbox-border-color: #cbd5e1;
                --ht-checkbox-checked-background-color: #1f47e0;
                --ht-checkbox-checked-border-color: #1f47e0;

                /* El resto del tema resuelve estas con light-dark(), que depende
                   de `color-scheme`. Se fijan por modo, igual que las de arriba. */
                --ht-cell-editor-background-color: #ffffff;
                --ht-cell-editor-foreground-color: #1e293b;
                --ht-placeholder-color: #94a3b8;
                --ht-input-disabled-background-color: #f1f5f9;
                --ht-link-color: #1a37b5;
                --ht-menu-item-hover-color: #eef4ff;
                --ht-menu-shadow-color: rgba(15, 23, 42, .18);
                --ht-shadow-color: rgba(15, 23, 42, .12);
                --ht-scrollbar-track-color: #f1f5f9;
                --ht-scrollbar-thumb-color: #cbd5e1;
                --ht-hidden-indicator-color: #94a3b8;
                --ht-resize-indicator-color: #94a3b8;
                --ht-header-row-highlighted-background-color: #eef4ff;
                --ht-header-row-highlighted-foreground-color: #1a37b5;
                --ht-header-active-border-color: #1f47e0;
            }

            /* Especificidad 0,2,0: gana a .ht-theme-main del tema (0,1,0). */
            .dark .ht-theme-main {
                color-scheme: dark;

                --ht-background-color: #1e293b;
                --ht-background-secondary-color: #0f172a;
                --ht-foreground-color: #e2e8f0;
                --ht-foreground-secondary-color: #94a3b8;
                --ht-border-color: #334155;
                --ht-cell-horizontal-border-color: #293548;
                --ht-cell-vertical-border-color: #293548;
                --ht-cell-read-only-background-color: #1e293b;
                --ht-read-only-color: #e2e8f0;
                --ht-disabled-color: #64748b;

                --ht-header-background-color: #0f172a;
                --ht-header-foreground-color: #a8b6c8;
                --ht-header-row-background-color: #0f172a;
                --ht-header-row-foreground-color: #94a3b8;
                --ht-header-highlighted-background-color: #1b3190;
                --ht-header-highlighted-foreground-color: #d9e6ff;
                --ht-header-active-background-color: #1b3190;
                --ht-header-active-foreground-color: #d9e6ff;

                --ht-accent-color: #8eb6ff;
                --ht-cell-selection-background-color: rgba(51, 102, 245, .18);
                --ht-cell-selection-border-color: #8eb6ff;

                --ht-checkbox-background-color: #0f172a;
                --ht-checkbox-border-color: #475569;
                --ht-checkbox-checked-background-color: #3366f5;
                --ht-checkbox-checked-border-color: #3366f5;

                --ht-cell-editor-background-color: #0f172a;
                --ht-cell-editor-foreground-color: #e2e8f0;
                --ht-placeholder-color: #64748b;
                --ht-input-disabled-background-color: #1e293b;
                --ht-link-color: #8eb6ff;
                --ht-menu-item-hover-color: #1b3190;
                --ht-menu-shadow-color: rgba(0, 0, 0, .5);
                --ht-shadow-color: rgba(0, 0, 0, .45);
                --ht-scrollbar-track-color: #0f172a;
                --ht-scrollbar-thumb-color: #475569;
                --ht-hidden-indicator-color: #64748b;
                --ht-resize-indicator-color: #64748b;
                --ht-header-row-highlighted-background-color: #1b3190;
                --ht-header-row-highlighted-foreground-color: #d9e6ff;
                --ht-header-active-border-color: #8eb6ff;
            }

            /* HOT inyecta su CSS base en <head> al CONSTRUIR la tabla, o sea después
               de este bloque: en empate de especificidad gana la suya. Estas reglas
               suben a 0,3,0 para que el editor no dependa de ese orden. */
            .ht-theme-main .handsontableInputHolder .handsontableInput {
                color: #1e293b;
                background-color: #ffffff;
            }
            .dark .ht-theme-main .handsontableInputHolder .handsontableInput {
                color: #e2e8f0;
                background-color: #0f172a;
            }

            /* HOT usa z-index hasta 9999 en sus capas fijas (cabecera, columnas
               congeladas). Sin un contexto de apilamiento propio esas capas se
               montan sobre la barra superior (z-30) y el menú lateral (z-40).
               `isolation` lo confina sin tocar el layout ni recortar los menús. */
            .ht-theme-main { isolation: isolate; }

            .ht-theme-main .htCore th {
                letter-spacing: .02em;
                text-transform: uppercase;
                font-size: 10px;
            }

            /* Añade `ht-compacta` al contenedor cuando la rejilla tenga muchas
               columnas y no quepa en un portátil. Fila de 20px en vez de 27px.
               Especificidad 0,2,0 para ganarle siempre a .ht-theme-main. */
            .ht-theme-main.ht-compacta {
                --ht-font-size: 11px;
                --ht-line-height: 15px;
                --ht-cell-horizontal-padding: 6px;
                --ht-cell-vertical-padding: 2px;
            }
            .ht-theme-main.ht-compacta .htCore th { font-size: 9px; }

            /* Columna de selección: centrada (antes se veía como un bloque macizo). */
            .ht-theme-main td.col-seleccion { text-align: center; vertical-align: middle; }

            /* Resaltados de fila de verReportesV4.1.js, con variante oscura.
               Van con .htCore (0,3,0) para ganarle a `.handsontable .htDimmed`
               (0,2,0 + !important), que se aplica a toda celda de solo lectura y
               que HOT reinyecta en <head> después de este bloque. */
            .ht-theme-main .htCore td.fila-60-meses       { background: #fdf4ff !important; color: #701a75 !important; }
            .ht-theme-main .htCore td.fila-gracia         { background: #f0f9ff !important; color: #075985 !important; }
            .ht-theme-main .htCore td.fila-acta-p         { background: #f5f3ff !important; color: #4c1d95 !important; }
            .ht-theme-main .htCore td.celda-amarilla      { background: #fefce8 !important; color: #713f12 !important; }
            .dark .ht-theme-main .htCore td.fila-60-meses  { background: #4a1d4f !important; color: #f5d0fe !important; }
            .dark .ht-theme-main .htCore td.fila-gracia    { background: #0c4a6e !important; color: #bae6fd !important; }
            .dark .ht-theme-main .htCore td.fila-acta-p    { background: #3b2a70 !important; color: #ddd6fe !important; }
            .dark .ht-theme-main .htCore td.celda-amarilla { background: #57431a !important; color: #fef08a !important; }
        </style>
    @endpush

    @push('libs')
        <script src="{{ $hotBase }}/dist/handsontable.full.min.js"></script>
        <script src="{{ $hotBase }}/dist/languages/es-MX.min.js"></script>

        <script>
            /* Registro de instancias para poder congelar el redibujado durante las
               animaciones de la página. Las vistas envuelven su tabla:
                   hot = window.registrarHot(new Handsontable(el, opciones));
               y la sueltan en destroy() sin tener que acordarse de nada más. */
            window.hotInstancias = new Set();
            window.registrarHot = function (hot) {
                window.hotInstancias.add(hot);
                const destruir = hot.destroy.bind(hot);
                hot.destroy = function () { window.hotInstancias.delete(hot); destruir(); };
                return hot;
            };

            (function () {
                let congelado = false;
                let temporizador = null;

                window.addEventListener('ui-animando', function () {
                    if (window.hotInstancias.size === 0) return;

                    if (!congelado) {
                        congelado = true;
                        window.hotInstancias.forEach(function (h) {
                            try { h.suspendRender(); } catch (e) {}
                        });
                    }

                    clearTimeout(temporizador);
                    // 240 ms: la transición del menú dura 200.
                    temporizador = setTimeout(function () {
                        congelado = false;
                        window.hotInstancias.forEach(function (h) {
                            try { h.resumeRender(); h.refreshDimensions(); } catch (e) {}
                        });
                    }, 240);
                });
            })();
        </script>
    @endpush
@endonce
