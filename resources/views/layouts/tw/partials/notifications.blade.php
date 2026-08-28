{{-- Campana de notificaciones. Replica el comportamiento del widget de AdminLTE:
     sondeo periódico, badge con el total, y al abrir se marcan todas como leídas. --}}
<div x-data="notificaciones({
        urlDatos:      '{{ route('notifications.json') }}',
        urlLeida:      '{{ route('notifications.markAsRead') }}',
        urlTodasLeidas:'{{ route('notifications.markAllAsRead') }}',
        periodo: 60000,
     })"
     @keydown.escape.window="abierto = false"
     class="relative">

    <button type="button" @click="alternar()" :aria-expanded="abierto"
            class="tw-btn-ghost relative h-10 w-10 p-0" aria-label="Notificaciones">
        <i class="fas fa-bell"></i>
        <span x-show="total > 0" x-cloak x-transition
              class="absolute -right-0.5 -top-0.5 flex min-w-[1.15rem] items-center justify-center rounded-full
                     bg-red-600 px-1 text-[10px] font-bold leading-[1.15rem] text-white ring-2 ring-white
                     dark:ring-slate-800"
              x-text="total > 99 ? '99+' : total"></span>
    </button>

    <div x-show="abierto" x-cloak x-transition.origin.top.right
         @click.outside="abierto = false"
         class="absolute right-0 z-50 mt-2 w-[22rem] overflow-hidden rounded-2xl border border-slate-200 bg-white
                shadow-xl dark:border-slate-700 dark:bg-slate-800">

        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-700">
            <span class="text-sm font-semibold text-slate-900 dark:text-white">Notificaciones</span>
            <span class="pill-slate" x-text="`${items.length} recientes`"></span>
        </div>

        <div class="max-h-[22rem] overflow-y-auto">
            <template x-for="n in items" :key="n.id">
                <div class="group flex gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0
                            hover:bg-slate-50 dark:border-slate-700/50 dark:hover:bg-slate-700/40">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-200">
                        <i :class="n.icon" class="text-xs"></i>
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold leading-snug text-slate-800 dark:text-slate-100" x-text="n.text"></p>
                        <p class="mt-0.5 text-xs text-slate-500" x-text="`${n.user} · ${n.time}`"></p>
                        <a x-show="n.link" :href="n.link" @click.prevent="abrirEnlace(n)"
                           class="mt-1 inline-block text-xs font-medium text-brand-600 hover:underline">Ver más</a>
                    </div>

                    <button type="button" @click="descartar(n)"
                            class="h-7 w-7 shrink-0 rounded-lg text-slate-300 transition hover:bg-red-50 hover:text-red-600
                                   dark:hover:bg-red-950/40"
                            aria-label="Descartar notificación">
                        <i class="fas fa-trash text-xs"></i>
                    </button>
                </div>
            </template>

            <p x-show="!items.length && !cargando" class="px-4 py-12 text-center text-sm text-slate-400">
                <i class="fas fa-bell-slash mb-2 block text-xl text-slate-300"></i>
                No tienes notificaciones nuevas.
            </p>
            <p x-show="cargando" x-cloak class="px-4 py-12 text-center text-sm text-slate-400">
                <i class="fas fa-circle-notch fa-spin"></i> Cargando…
            </p>
        </div>

        <a href="{{ route('notifications.index') }}"
           class="block border-t border-slate-100 px-4 py-3 text-center text-sm font-medium text-brand-600
                  hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/40">
            Ver todas las notificaciones
        </a>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('notificaciones', ({ urlDatos, urlLeida, urlTodasLeidas, periodo }) => ({
        abierto: false,
        cargando: false,
        total: 0,
        items: [],

        init() {
            this.cargar();
            setInterval(() => { if (!this.abierto) this.cargar(); }, periodo);
        },

        async cargar() {
            this.cargando = true;
            try {
                const data = await window.api(urlDatos);
                this.total = data.total;
                this.items = data.items;
            } catch (e) {
                console.error('No se pudieron cargar las notificaciones', e);
            } finally {
                this.cargando = false;
            }
        },

        alternar() {
            this.abierto = !this.abierto;
            // Mismo comportamiento que AdminLTE: al abrir, se marcan todas como leídas.
            if (this.abierto && this.total > 0) this.marcarTodas();
        },

        async marcarTodas() {
            try {
                await window.api(urlTodasLeidas);
                this.total = 0;
            } catch (e) {
                console.error('No se pudieron marcar como leídas', e);
            }
        },

        async descartar(n) {
            this.items = this.items.filter(i => i.id !== n.id);
            this.total = Math.max(0, this.total - 1);
            try {
                await window.api(`${urlLeida}?notification_id=${encodeURIComponent(n.id)}`);
            } catch (e) {
                console.error('No se pudo descartar la notificación', e);
            }
        },

        async abrirEnlace(n) {
            // Se marca como leída antes de navegar, como hacía master.js.
            try { await window.api(`${urlLeida}?notification_id=${encodeURIComponent(n.id)}`); } catch (e) {}
            window.location.href = n.link;
        },
    }));
});
</script>
@endpush
@endonce
