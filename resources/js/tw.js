import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import Swal from 'sweetalert2';

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

/* Sweetalert2 ya se usa en 14 vistas: lo dejamos global para no reescribirlas. */
window.Swal = Swal;

/* Store global de UI (sidebar + tema), persistido en localStorage. */
Alpine.store('ui', {
    sidebarOpen: Alpine.$persist(true).as('ui.sidebarOpen'),   // escritorio: expandido/contraído
    mobileOpen: false,                                          // móvil: cajón lateral
    dark: Alpine.$persist(false).as('ui.dark'),

    /* En móvil el cajón siempre se ve completo, aunque en escritorio
       esté contraído: de ahí que las etiquetas miren `expanded`. */
    get expanded() { return this.sidebarOpen || this.mobileOpen; },

    toggleSidebar() { this.sidebarOpen = !this.sidebarOpen; },
    toggleMobile() { this.mobileOpen = !this.mobileOpen; },
    toggleDark() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
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

window.Alpine = Alpine;
Alpine.start();
