import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import registrarAlertas from './alerts';
import registrarCalendario from './datepicker';

Alpine.plugin(persist);
Alpine.plugin(collapse);
Alpine.plugin(focus);

/* CSRF disponible para fetch() en las vistas. */
window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

/* Helper de peticiones: reemplaza $.ajax en las vistas migradas. */
window.api = async function (url, { method = 'GET', body = null, headers = {} } = {}) {
    const res = await fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': window.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
            ...(body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
            ...headers,
        },
        body: body instanceof FormData ? body : body ? JSON.stringify(body) : null,
    });

    const data = res.headers.get('content-type')?.includes('application/json')
        ? await res.json()
        : await res.text();

    if (!res.ok) throw Object.assign(new Error(`HTTP ${res.status}`), { status: res.status, data });
    return data;
};

/* Bloqueo de scroll compartido entre ventanas superpuestas.
   `x-trap.noscroll` guarda y restaura el overflow del body por su cuenta: con
   dos ventanas encima (un modal y una alerta), la segunda guardaba el 'hidden'
   que había dejado la primera y al cerrarse lo restauraba, dejando la página
   sin scroll para siempre. Con un contador solo se libera al cerrar la última. */
Alpine.store('scroll', {
    abiertos: 0,
    previo: '',
    bloquear() {
        if (this.abiertos === 0) {
            this.previo = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
        }
        this.abiertos++;
    },
    liberar() {
        this.abiertos = Math.max(0, this.abiertos - 1);
        if (this.abiertos === 0) document.body.style.overflow = this.previo;
    },
});

/* Store global de UI (sidebar + tema), persistido en localStorage. */
Alpine.store('ui', {
    sidebarOpen: Alpine.$persist(true).as('ui.sidebarOpen'),   // escritorio: expandido/contraído
    mobileOpen: false,                                          // móvil: cajón lateral
    dark: Alpine.$persist(false).as('ui.dark'),

    /* En móvil el cajón siempre se ve completo, aunque en escritorio
       esté contraído: de ahí que las etiquetas miren `expanded`. */
    get expanded() { return this.sidebarOpen || this.mobileOpen; },

    /* Handsontable observa el tamaño de su contenedor y se redibuja en cada
       fotograma mientras el menú anima su ancho, lo que hace tartamudear la
       animación. Se avisa para poder congelar el redibujado durante ese rato. */
    animando() { window.dispatchEvent(new CustomEvent('ui-animando')); },
    toggleSidebar() { this.animando(); this.sidebarOpen = !this.sidebarOpen; },
    toggleMobile() { this.animando(); this.mobileOpen = !this.mobileOpen; },
    /* El cambio de tema se revela con un círculo que crece desde el botón.

       Lo hace la API de transiciones de vista: el navegador congela la página,
       aplica el cambio y nos deja animar la instantánea nueva. Por eso basta
       con tocar esta función; ninguna vista se entera.

       El `classList.toggle` va dentro de la devolución de llamada y de forma
       síncrona a propósito: el navegador toma la instantánea nueva en cuanto
       esta termina, y el efecto reactivo de Alpine sobre <html> llegaría un
       microtarea más tarde, con el retrato ya hecho. */
    toggleDark(evento = null) {
        const aplicar = () => {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
        };

        const menosMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (typeof document.startViewTransition !== 'function' || menosMovimiento) {
            aplicar();
            return;
        }

        /* Centro del círculo: el botón que se pulsó. Se mide ahora, no dentro
           de la promesa: `currentTarget` sólo existe mientras el evento se está
           despachando. Sin evento, cae en la esquina superior derecha, que es
           donde vive el interruptor. */
        const caja = evento?.currentTarget?.getBoundingClientRect?.();

        /* La referencia es clientWidth/clientHeight y no innerWidth/innerHeight:
           el primero descuenta las barras de desplazamiento, igual que el bloque
           sobre el que se dibuja la instantánea de la transición. */
        const raiz = document.documentElement;
        const ancho = raiz.clientWidth || 1;
        const alto = raiz.clientHeight || 1;

        /* El centro va en porcentaje, no en píxeles. El recorte se resuelve
           contra el bloque de la instantánea, que no tiene por qué medir lo
           mismo que la ventana —con el navegador ampliado o con barras de
           desplazamiento no coinciden—, y ahí el círculo salía desplazado. En
           relativo cae siempre sobre el botón, se cambie el tamaño que se
           cambie. Sin evento, la esquina donde vive el interruptor. */
        const x = caja ? ((caja.left + caja.width / 2) / ancho) * 100 : 96;
        const y = caja ? ((caja.top + caja.height / 2) / alto) * 100 : 4;

        const transicion = document.startViewTransition(aplicar);

        transicion.ready.then(() => {
            /* 150%: un radio en porcentaje se mide contra
               sqrt(ancho² + alto²) / sqrt(2), así que hay que pasar de 141,4%
               para tapar la esquina más lejana nazca donde nazca el círculo. */
            raiz.animate(
                { clipPath: [`circle(0% at ${x}% ${y}%)`, `circle(150% at ${x}% ${y}%)`] },
                { duration: 480, easing: 'cubic-bezier(.4, 0, .2, 1)',
                  pseudoElement: '::view-transition-new(root)' },
            );
        }).catch(() => {});
    },
});

/* Componente reutilizable para toasts/flash de sesión. */
Alpine.data('flash', (message = '', type = 'success') => ({
    show: Boolean(message),
    message,
    type,
    init() {
        if (this.show) setTimeout(() => (this.show = false), 5000);
    },
}));

/* Define window.Swal con la API de SweetAlert sobre nuestro sistema. */
registrarAlertas(Alpine);

/* Sustituye el calendario nativo de los <input type="date"> por el nuestro. */
registrarCalendario();

window.Alpine = Alpine;
Alpine.start();
