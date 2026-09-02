/* ------------------------------------------------------------------
   Sistema de alertas propio (Alpine + Tailwind).
   Sustituye a SweetAlert2 conservando su API pública, porque hay 259
   llamadas a Swal.fire() repartidas por el proyecto: se expone
   window.Swal con la misma forma para no tocar los sitios de llamada.

   Soportado: title, text, html, icon (y el `type` heredado), toast,
   position, timer, timerProgressBar, showConfirmButton/Cancel/Deny,
   *ButtonText, *ButtonColor, allowOutsideClick, allowEscapeKey, width,
   footer, input (+Placeholder/Attributes/Value/Options), preConfirm,
   didOpen, customClass; y Swal.close / showLoading /
   showValidationMessage / getPopup / isVisible.
   El resultado mantiene { isConfirmed, isDenied, isDismissed, value }.
   ------------------------------------------------------------------ */

const ICONOS = {
    success:  { fa: 'fa-check',                tinte: 'emerald' },
    error:    { fa: 'fa-xmark',                tinte: 'rose'    },
    warning:  { fa: 'fa-exclamation',          tinte: 'amber'   },
    info:     { fa: 'fa-info',                 tinte: 'sky'     },
    question: { fa: 'fa-question',             tinte: 'violet'  },
};

export default function registrarAlertas(Alpine) {
    /* `dialogo` nunca es null y `visible` va aparte: x-transition sólo
       funciona con x-show, no con x-if, y x-show evalúa los bindings de
       sus hijos aunque esté oculto. Con un objeto por defecto siempre hay
       algo que leer y la salida se puede animar. */
    const dialogoVacio = () => ({
        titulo: '', texto: '', html: null, footer: null, icono: null,
        mostrarConfirmar: true, mostrarCancelar: false, mostrarDenegar: false,
        textoConfirmar: 'Aceptar', textoCancelar: 'Cancelar', textoDenegar: 'No',
        colorConfirmar: null, colorCancelar: null,
        input: null, inputPlaceholder: '', inputAttributes: {}, inputOptions: null, valor: '',
        ancho: null, claseExtra: '', cerrarFuera: true, cerrarEscape: true,
        validacion: '', cargando: false,
        _preConfirm: null, _resolver: null, _timer: null,
    });

    Alpine.store('alertas', {
        dialogo: dialogoVacio(),
        visible: false,
        toasts: [],
        secuencia: 0,

        cerrar(resultado) {
            if (!this.visible) return;
            const d = this.dialogo;
            if (d._timer) clearTimeout(d._timer);
            this.visible = false;
            d._resolver?.(resultado);
        },

        ocultarToast(id) {
            const t = this.toasts.find(t => t.id === id);
            if (!t || !t.visible) return;
            t.visible = false;                                   // dispara la salida
            setTimeout(() => this.quitarToast(id), 250);
        },

        quitarToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
    });

    const store = () => Alpine.store('alertas');

    const resultado = (extra = {}) => ({
        isConfirmed: false,
        isDenied: false,
        isDismissed: false,
        value: undefined,
        ...extra,
    });

    /* Acepta fire(opciones) y el fire(title, text, icon) heredado. */
    function normalizar(a, b, c) {
        const o = (typeof a === 'string' || a == null)
            ? { title: a ?? '', text: b ?? '', icon: c }
            : { ...a };

        // `type` es la opción de SweetAlert v9, retirada en v10. Se acepta
        // para que las llamadas antiguas sigan mostrando icono.
        if (!o.icon && o.type) o.icon = o.type;

        return o;
    }

    async function fire(a, b, c) {
        const o = normalizar(a, b, c);

        if (o.toast) return dispararToast(o);
        return dispararDialogo(o);
    }

    function dispararToast(o) {
        const id = ++store().secuencia;
        const icono = ICONOS[o.icon] ?? null;

        store().toasts.push({
            id,
            titulo: o.title ?? '',
            texto: o.text ?? '',
            icono,
            posicion: o.position ?? 'top-end',
            visible: false,
        });

        // Se monta oculto y se muestra en el tick siguiente, si no la
        // transición de entrada no llega a ejecutarse.
        Alpine.nextTick(() => {
            const t = store().toasts.find(t => t.id === id);
            if (t) t.visible = true;
        });

        const duracion = o.timer ?? 4000;
        setTimeout(() => store().ocultarToast(id), duracion);

        return Promise.resolve(resultado({ isDismissed: true }));
    }

    function dispararDialogo(o) {
        // Sólo un diálogo a la vez: el anterior se descarta, igual que SweetAlert.
        if (store().visible) store().cerrar(resultado({ isDismissed: true }));

        return new Promise((resolver) => {
            const d = {
                ...dialogoVacio(),
                id: ++store().secuencia,
                titulo: o.title ?? '',
                texto: o.text ?? '',
                html: o.html ?? null,
                footer: o.footer ?? null,
                icono: ICONOS[o.icon] ?? null,

                mostrarConfirmar: o.showConfirmButton !== false,
                mostrarCancelar: !!o.showCancelButton,
                mostrarDenegar: !!o.showDenyButton,
                textoConfirmar: o.confirmButtonText ?? 'Aceptar',
                textoCancelar: o.cancelButtonText ?? 'Cancelar',
                textoDenegar: o.denyButtonText ?? 'No',
                colorConfirmar: o.confirmButtonColor ?? null,
                colorCancelar: o.cancelButtonColor ?? null,

                input: o.input ?? null,
                inputPlaceholder: o.inputPlaceholder ?? '',
                inputAttributes: o.inputAttributes ?? {},
                inputOptions: o.inputOptions ?? null,
                valor: o.inputValue ?? '',

                ancho: o.width ?? null,
                claseExtra: o.customClass?.popup ?? '',
                cerrarFuera: o.allowOutsideClick !== false,
                cerrarEscape: o.allowEscapeKey !== false,

                validacion: '',
                cargando: false,

                _preConfirm: o.preConfirm ?? null,
                _resolver: resolver,
                _timer: null,
            };

            store().dialogo = d;
            store().visible = true;

            Alpine.nextTick(() => {
                if (typeof o.didOpen === 'function') o.didOpen(getPopup());
                const foco = document.querySelector('[data-alerta-foco]');
                if (foco) foco.focus();
            });

            if (o.timer) {
                d._timer = setTimeout(
                    () => store().cerrar(resultado({ isDismissed: true })),
                    o.timer,
                );
            }
        });
    }

    /* Confirmar pasa por preConfirm: si devuelve false (o deja un mensaje
       de validación), el diálogo permanece abierto. */
    async function confirmar() {
        if (!store().visible) return;
        const d = store().dialogo;

        d.validacion = '';
        let valor = d.input ? d.valor : true;

        if (d._preConfirm) {
            d.cargando = true;
            try {
                const devuelto = await d._preConfirm(valor);
                if (devuelto === false || store().dialogo.validacion) {
                    d.cargando = false;
                    return;
                }
                if (devuelto !== undefined) valor = devuelto;
            } catch (e) {
                d.cargando = false;
                d.validacion = e?.message ?? 'Ocurrió un error';
                return;
            }
            d.cargando = false;
        }

        store().cerrar(resultado({ isConfirmed: true, value: valor }));
    }

    const getPopup = () => document.getElementById('alerta-popup');

    window.Swal = {
        fire,
        close: () => store().cerrar(resultado({ isDismissed: true })),
        isVisible: () => store().visible,
        getPopup,
        showLoading: () => { store().dialogo.cargando = true; },
        hideLoading: () => { store().dialogo.cargando = false; },
        showValidationMessage: (m) => { store().dialogo.validacion = m; },
        resetValidationMessage: () => { store().dialogo.validacion = ''; },
        // Se exponen para el markup de Blade
        _confirmar: confirmar,
        _denegar: () => store().cerrar(resultado({ isDenied: true })),
        _cancelar: () => store().cerrar(resultado({ isDismissed: true })),
    };

    window.Swal.mixin = (base) => ({
        fire: (a, b, c) => fire({ ...base, ...normalizar(a, b, c) }),
    });
}
