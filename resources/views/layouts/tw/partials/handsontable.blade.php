{{-- Handsontable, declarado en un solo sitio.
     Úsalo con: @include('layouts.tw.partials.handsontable')
     y pon class="ht-theme-main" en el contenedor de la grilla.

     Dos cosas que hay que respetar:
     · Desde la v15 el CSS vive en styles/ (no en dist/) y se divide en base + tema.
       La versión se replica en el CSS del tema; manténlas iguales.
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
                /* Tamaño del rótulo de la cabecera. Va aparte del de la celda
                   porque son cosas distintas: el encabezado se lee de un
                   vistazo y en versalitas, y con muchas columnas conviene que
                   sea el que menos sitio ocupe. */
                --ht-header-font-size: 9px;
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

                /* Rojo del contrato repetido en PQRS. Va como token del tema y
                   no fijo en el renderizador porque tiene que leerse sobre el
                   fondo de la tabla, que cambia con el modo. */
                --ht-alerta-color: #d32f2f;

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

                /* Más claro que en modo claro: el #d32f2f sobre #1e293b se
                   queda en 2,7:1 y no se lee. */
                --ht-alerta-color: #f87171;

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
            }

            /* El tamaño del encabezado tiene que ponerse AQUÍ, en el <span>, y
               no en el <th>. handsontable.css trae:

                   .colHeader { font-size: var(--ht-font-size) }

               es decir, el rótulo lleva su propio font-size, y un font-size
               propio gana siempre sobre lo que se herede del padre. Por eso el
               `font-size: 10px` que había en el <th> no llegaba nunca al texto:
               la cabecera se estaba pintando al tamaño de las celdas y salía
               cortada por más que se ensancharan las columnas. */
            .ht-theme-main .htCore th .colHeader {
                font-size: var(--ht-header-font-size);
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
            .ht-theme-main.ht-compacta { --ht-header-font-size: 8px; }

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
                hot.destroy = function () {
                    window.hotInstancias.delete(hot);
                    window.hotCentradas?.delete(hot);
                    destruir();
                };
                return hot;
            };

            /* Ancho de columna para que el encabezado se lea entero.

               Sin esto las columnas se quedan en el ancho por defecto de
               Handsontable y los encabezados salen cortados: "MIÉRCOLE…" en vez
               de "MIÉRCOLES 03", justo con el número —el dato que distingue una
               columna de otra— fuera de la vista.

               El tamaño se lee de --ht-header-font-size, que es el del <span>
               que contiene el rótulo. Medir con el font-size del <th> no vale:
               ese nunca llega al texto (ver la regla de .colHeader más arriba).

               Al hueco del rótulo hay que descontarle el relleno de la celda,
               el separador flex y el botón del desplegable de filtros, que mide
               --ht-icon-button-hit-area-size (24px). Encima va un margen del
               10%: medir con canvas y lo que acaba pintando el navegador no
               coinciden al píxel —la fuente puede no estar cargada todavía
               cuando se mide, y ahí cae en la de reserva—, y quedarse corto
               significa volver a ver puntos suspensivos. */
            window.anchoDeCabecera = (function () {
                const MINIMO  = 48;    // por debajo la celda queda apretada
                const MAXIMO  = 200;
                const BOTON   = 24;    // --ht-icon-button-hit-area-size
                const HUECO   = 4;     // gap del flex + borde de la celda
                const MARGEN  = 1.10;  // colchón sobre el texto medido

                let medidor = null;

                const leer = (estilos, prop, respaldo) =>
                    (estilos?.getPropertyValue(prop) || '').trim() || respaldo;

                return function (texto, contenedor) {
                    if (!medidor) {
                        medidor = document.createElement('canvas').getContext('2d');
                    }

                    const raiz = contenedor ?? document.querySelector('.ht-theme-main');
                    const est = raiz ? getComputedStyle(raiz) : null;

                    const tam     = leer(est, '--ht-header-font-size', '9px');
                    const familia = leer(est, '--ht-font-family', 'sans-serif');
                    const peso    = leer(est, '--ht-header-font-weight', '600');
                    const relleno = parseFloat(leer(est, '--ht-cell-horizontal-padding', '6px')) * 2;

                    medidor.font = `${peso} ${tam} ${familia}`;

                    /* La cabecera va en versalitas y con letter-spacing, que
                       measureText no conoce: se pasa a mayúsculas y se suma el
                       espaciado a mano. */
                    const t = String(texto ?? '').toUpperCase();
                    const px = parseFloat(tam);
                    const rotulo = (medidor.measureText(t).width + t.length * px * 0.02) * MARGEN;

                    return Math.min(MAXIMO, Math.max(MINIMO, Math.ceil(rotulo + relleno + HUECO + BOTON)));
                };
            })();

            /* Centrado de rejillas estrechas.
               En pantallas anchas una tabla de pocas columnas quedaba pegada al
               borde izquierdo con media tarjeta vacía a la derecha. No se puede
               centrar por CSS: Handsontable coloca sus capas superpuestas
               (cabeceras y columnas fijas) contra el borde izquierdo del
               contenedor, así que desplazar la tabla las descuadraría.
               En su lugar se le da al contenedor el ancho exacto de la rejilla
               y se centra ese contenedor, que sí es DOM propio. */
            window.hotCentradas = new Set();

            window.centrarHot = function (hot) {
                if (!hot || hot.isDestroyed) return;
                const raiz = hot.rootElement;
                const padre = raiz.parentElement;
                if (!padre) return;

                window.hotCentradas.add(hot);

                // Se suelta el ancho anterior para medir el espacio real.
                raiz.style.width = '';
                raiz.style.marginInline = '';

                // Sumar getColWidth en vez de medir el <table>: con virtualización
                // horizontal el DOM solo tiene las columnas visibles, así que una
                // tabla ancha se habría medido como si cupiese justo.
                let ancho = 0;
                for (let c = 0; c < hot.countCols(); c++) ancho += hot.getColWidth(c);

                const encabezado = raiz.querySelector('.ht_master .htCore tbody tr > th');
                if (encabezado) ancho += encabezado.offsetWidth;

                // +2 por los bordes; el margen evita centrar por uno o dos píxeles.
                if (ancho <= 0 || ancho + 2 >= padre.clientWidth) {
                    hot.refreshDimensions();
                    return;
                }

                raiz.style.width = (ancho + 2) + 'px';
                raiz.style.marginInline = 'auto';
                hot.refreshDimensions();

                /* Comprobación: si al ceñir el contenedor la rejilla necesita
                   scroll horizontal, el ancho calculado se quedó corto (las
                   anchuras de columna aún no estaban medidas, por ejemplo) y
                   habríamos encogido la tabla en vez de centrarla. Se revierte
                   y se deja a lo ancho, que es el comportamiento de siempre. */
                const holder = raiz.querySelector('.ht_master .wtHolder');
                if (holder && holder.scrollWidth > holder.clientWidth + 1) {
                    raiz.style.width = '';
                    raiz.style.marginInline = '';
                    hot.refreshDimensions();
                }
            };

            (function () {
                let ajuste = null;
                window.addEventListener('resize', function () {
                    clearTimeout(ajuste);
                    ajuste = setTimeout(function () {
                        window.hotCentradas.forEach(function (h) {
                            try { window.centrarHot(h); } catch (e) {}
                        });
                    }, 150);
                });
            })();

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
