@extends('layouts.tw.app')

@section('title', 'Gestión de Usuarios')

@section('content_header')
    <h1>Gestión de Usuarios</h1>
@endsection

@php
    /* Payload explícito para Alpine: evita volcar el modelo completo al HTML. */
    $usersPayload = $users->map(fn ($u) => [
        'id'             => $u->id,
        'name'           => $u->name,
        'email'          => $u->email,
        'type_id'        => $u->type_id,
        'identification' => $u->identification,
        'state'          => (bool) $u->state,
        'roles'          => $u->roles->pluck('name')->values(),
        'permissions'    => $u->permissions->pluck('name')->values(),
    ])->values();
@endphp

@section('content')
    <div x-data="usersTable({
            users: @js($usersPayload),
            roles: @js($roles->pluck('name')->values()),
            urls: {
                permissions: '{{ route('profile.getDataPermissions') }}',
                update: '{{ route('admin.update') }}',
                changeStatus: '{{ route('admin.changeStatus', ['user' => '__ID__']) }}',
                invitacion: '{{ route('admin.enlaceRegistro') }}',
            },
         })"
         class="tw-card">

        {{-- Barra de herramientas --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-700">
            <label class="relative w-full sm:max-w-xs">
                <span class="sr-only">Buscar usuarios</span>
                <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="search"
                       x-model.debounce.200ms="search"
                       placeholder="Buscar por nombre, email o identificación…"
                       class="tw-input pl-9">
            </label>

            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <span x-text="`${filtered.length} de ${users.length} usuarios`"></span>
                <select x-model.number="perPage" class="tw-select w-auto py-1.5">
                    <template x-for="n in [10, 25, 50, 100]" :key="n">
                        <option :value="n" x-text="`${n} / página`"></option>
                    </template>
                </select>

                {{-- El registro ya no es público: para que alguien se registre
                     hay que darle este enlace, que caduca solo. --}}
                <button type="button" class="tw-btn-primary tw-btn-sm" @click="invitar()"
                        :disabled="invitando">
                    <i class="fas" :class="invitando ? 'fa-spinner fa-spin' : 'fa-user-plus'"></i>
                    Invitar usuario
                </button>
            </div>
        </div>

        {{-- Enlace de invitación recién generado --}}
        <div x-show="invitacion" x-cloak
             class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-brand-50/60 px-4 py-3
                    dark:border-slate-700 dark:bg-brand-900/20">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-brand-700 dark:text-brand-300">
                    Enlace de registro · caduca el <span x-text="invitacionCaduca"></span>
                </p>
                <p class="mt-1 truncate font-mono text-xs text-slate-600 dark:text-slate-300"
                   x-text="invitacion" :title="invitacion"></p>
                <p class="tw-hint mt-1">
                    Quien lo use creará su cuenta inactiva; actívala desde esta lista cuando llegue.
                </p>
            </div>
            <button type="button" class="tw-btn-secondary tw-btn-sm" @click="copiarInvitacion()">
                <i class="fas" :class="copiado ? 'fa-check' : 'fa-copy'"></i>
                <span x-text="copiado ? 'Copiado' : 'Copiar'"></span>
            </button>
            <button type="button" class="tw-btn-ghost tw-btn-sm" @click="invitacion = ''" aria-label="Cerrar">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        {{-- Tabla --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900/50">
                    <tr>
                        @foreach (['name' => 'Nombre', 'email' => 'Email', 'type_id' => 'Tipo ID', 'identification' => 'Identificación'] as $key => $label)
                            <th scope="col" class="px-4 py-3">
                                <button type="button" @click="sortBy('{{ $key }}')" class="inline-flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200">
                                    {{ $label }}
                                    <i class="fas text-[0.625rem]"
                                       :class="sort.key === '{{ $key }}' ? (sort.dir === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down') : 'fa-sort opacity-30'"></i>
                                </button>
                            </th>
                        @endforeach
                        <th scope="col" class="px-4 py-3">Roles</th>
                        <th scope="col" class="px-4 py-3">Permisos</th>
                        <th scope="col" class="px-4 py-3">Estado</th>
                        <th scope="col" class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <template x-for="user in paginated" :key="user.id">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40">
                            <td class="px-4 py-3 font-medium" x-text="user.name"></td>
                            <td class="px-4 py-3 text-slate-500" x-text="user.email"></td>
                            <td class="px-4 py-3" x-text="user.type_id"></td>
                            <td class="px-4 py-3" x-text="user.identification"></td>

                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="role in user.roles" :key="role">
                                        <span class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-800
                                                     dark:bg-brand-900 dark:text-brand-100" x-text="role"></span>
                                    </template>
                                </div>
                            </td>

                            {{-- Permisos: los 3 primeros + "ver todos", sin manipular el DOM a mano --}}
                            <td class="px-4 py-3" x-data="{ expanded: false }">
                                <div class="flex flex-wrap items-center gap-1">
                                    <template x-for="p in (expanded ? user.permissions : user.permissions.slice(0, 3))" :key="p">
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700
                                                     dark:bg-slate-700 dark:text-slate-200" x-text="p"></span>
                                    </template>

                                    <button type="button"
                                            x-show="user.permissions.length > 3"
                                            @click="expanded = !expanded"
                                            class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-medium text-slate-600
                                                   hover:bg-slate-300 dark:bg-slate-600 dark:text-slate-200"
                                            x-text="expanded ? 'ver menos' : `+${user.permissions.length - 3} más`"></button>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium"
                                      :class="user.state
                                          ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100'
                                          : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100'">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="user.state ? 'bg-green-500' : 'bg-red-500'"></span>
                                    <span x-text="user.state ? 'Activo' : 'Inactivo'"></span>
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="openEdit(user)" class="tw-btn-secondary py-1.5 text-xs">
                                        <i class="fas fa-pen"></i> Editar
                                    </button>
                                    <button type="button"
                                            @click="toggleState(user)"
                                            class="py-1.5 text-xs"
                                            :class="user.state ? 'tw-btn-danger' : 'tw-btn-primary'"
                                            x-text="user.state ? 'Desactivar' : 'Activar'"></button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!filtered.length">
                        <td colspan="8" class="px-4 py-10 text-center text-slate-500">
                            Nada encontrado — lo siento.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-4 text-sm dark:border-slate-700"
             x-show="totalPages > 1">
            <span class="text-slate-500" x-text="`Página ${page} de ${totalPages}`"></span>
            <div class="flex gap-2">
                <button type="button" class="tw-btn-secondary py-1.5 text-xs" @click="page--" :disabled="page === 1">Anterior</button>
                <button type="button" class="tw-btn-secondary py-1.5 text-xs" @click="page++" :disabled="page === totalPages">Siguiente</button>
            </div>
        </div>

        @include('users.partials.edit-modal')
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('usersTable', ({ users, roles, urls }) => ({
                users,
                roles,
                urls,

                search: '',
                invitacion: '',
                invitacionCaduca: '',
                invitando: false,
                copiado: false,
                sort: { key: 'name', dir: 'asc' },
                page: 1,
                perPage: 10,

                /* --- Estado del modal de edición ---
                   `modal` es la bandera que ve el componente de ventana y
                   `editing` el registro que se está editando. Iban en una sola
                   variable, con el objeto haciendo de booleano: x-trap compara
                   su valor anterior por identidad y está escrito para banderas,
                   no para objetos. El resto de la aplicación usa este mismo par. */
                modal: null,
                editing: null,
                form: { name: '', email: '', type_id: '', identification: '', role: '' },
                assigned: [],
                available: [],
                assignedSel: [],
                availableSel: [],
                loadingPerms: false,
                saving: false,
                changingPassword: false,
                error: '',
                invalidos: [],
                password: { nueva: '', confirmar: '', showNueva: false, showConfirmar: false },

                get filtered() {
                    const q = this.search.trim().toLowerCase();
                    const rows = q
                        ? this.users.filter(u =>
                            [u.name, u.email, u.identification, ...u.roles].join(' ').toLowerCase().includes(q))
                        : this.users;

                    const { key, dir } = this.sort;
                    return [...rows].sort((a, b) =>
                        String(a[key] ?? '').localeCompare(String(b[key] ?? ''), 'es', { numeric: true })
                        * (dir === 'asc' ? 1 : -1));
                },

                get totalPages() { return Math.max(1, Math.ceil(this.filtered.length / this.perPage)); },

                get paginated() {
                    // Si el filtro dejó la página actual fuera de rango, vuelve a la última válida.
                    if (this.page > this.totalPages) this.page = this.totalPages;
                    return this.filtered.slice((this.page - 1) * this.perPage, this.page * this.perPage);
                },

                async invitar() {
                    this.invitando = true;
                    this.copiado = false;
                    try {
                        const r = await window.api(this.urls.invitacion, { method: 'POST' });
                        this.invitacion = r.url;
                        this.invitacionCaduca = r.caduca;
                    } catch (e) {
                        window.Swal.fire({ icon: 'error', title: 'Error',
                                           text: 'No se pudo generar el enlace de registro.' });
                    } finally {
                        this.invitando = false;
                    }
                },

                async copiarInvitacion() {
                    try {
                        await navigator.clipboard.writeText(this.invitacion);
                        this.copiado = true;
                        setTimeout(() => (this.copiado = false), 2000);
                    } catch (e) {
                        /* El portapapeles necesita contexto seguro (HTTPS o
                           localhost); si no está, el enlace se queda a la vista
                           para copiarlo a mano. */
                        window.Swal.fire({ icon: 'info', title: 'Copia el enlace a mano',
                                           text: this.invitacion });
                    }
                },

                sortBy(key) {
                    this.sort = this.sort.key === key
                        ? { key, dir: this.sort.dir === 'asc' ? 'desc' : 'asc' }
                        : { key, dir: 'asc' };
                },

                async openEdit(user) {
                    this.editing = user;
                    this.modal = 'editar';
                    this.form = {
                        name: user.name,
                        email: user.email,
                        type_id: user.type_id,
                        identification: user.identification,
                        role: user.roles[0] ?? '',
                    };
                    this.resetPassword();
                    this.error = '';
                    this.invalidos = [];
                    this.assigned = [];
                    this.available = [];
                    this.assignedSel = [];
                    this.availableSel = [];
                    this.loadingPerms = true;

                    try {
                        const data = await window.api(this.urls.permissions, {
                            method: 'POST',
                            body: { id: user.id },
                        });
                        this.assigned = data.asignadas.map(p => p.name);
                        this.available = data.disponibles.map(p => p.name);
                    } catch (e) {
                        this.error = 'No se pudieron cargar los permisos.';
                    } finally {
                        this.loadingPerms = false;
                    }
                },

                closeEdit() {
                    // La bandera primero: lo demás son campos que el modal ya
                    // no muestra y no deben cambiar mientras se está cerrando.
                    this.modal = null;
                    this.editing = null;
                    this.resetPassword();
                    this.error = '';
                    this.invalidos = [];
                },

                togglePassword() {
                    if (this.changingPassword) this.resetPassword();
                    else this.changingPassword = true;
                    this.error = '';
                    this.invalidos = [];
                },

                claseCampo(campo) {
                    return this.invalidos.includes(campo)
                        ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                        : '';
                },

                /* Las mismas reglas que aplica el servidor. Comprobarlas aquí
                   evita el viaje y, sobre todo, el aviso flotante encima del
                   modal: el mensaje sale dentro y el campo se marca. */
                revisar() {
                    this.invalidos = [];
                    if (!this.form.name.trim()) this.invalidos.push('name');
                    if (!this.form.email.trim()) this.invalidos.push('email');
                    if (this.invalidos.length) return 'El nombre y el email son obligatorios.';

                    if (!this.changingPassword) return '';

                    const nueva = this.password.nueva;
                    const confirmar = this.password.confirmar;

                    if (!nueva.trim() || !confirmar.trim()) {
                        this.invalidos = [!nueva.trim() ? 'nueva' : null,
                                          !confirmar.trim() ? 'confirmar' : null].filter(Boolean);
                        return 'Escribe la contraseña nueva y su confirmación.';
                    }
                    if (nueva.length < 8) {
                        this.invalidos = ['nueva'];
                        return 'La contraseña debe ser de mínimo 8 caracteres.';
                    }
                    if (nueva !== confirmar) {
                        this.invalidos = ['nueva', 'confirmar'];
                        return 'Las contraseñas no coinciden.';
                    }
                    return '';
                },

                resetPassword() {
                    this.changingPassword = false;
                    this.password = { nueva: '', confirmar: '', showNueva: false, showConfirmar: false };
                },

                move(from, to, selection) {
                    this[to].push(...this[selection]);
                    this[from] = this[from].filter(p => !this[selection].includes(p));
                    this[selection] = [];
                },

                async save() {
                    this.error = this.revisar();
                    if (this.error) return;

                    this.saving = true;
                    try {
                        const res = await window.api(this.urls.update, {
                            method: 'POST',
                            body: {
                                id: this.editing.id,
                                nombres: this.form.name,
                                email: this.form.email,
                                roles: this.form.role,
                                assignedPermissions: this.assigned,
                                revokedPermissions: this.available,
                                claveNueva: this.changingPassword ? this.password.nueva : null,
                                claveConfirmar: this.changingPassword ? this.password.confirmar : null,
                            },
                        });

                        if (res.status === 'success') {
                            // La tabla se re-renderiza sola: sólo hay que actualizar el estado.
                            Object.assign(this.editing, {
                                name: res.user.name,
                                email: res.user.email,
                                roles: [this.form.role],
                                permissions: [...this.assigned],
                            });
                            this.closeEdit();
                            window.Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                                               title: res.message ?? 'Usuario actualizado',
                                               timer: 3000, showConfirmButton: false });
                        } else {
                            // El modal se queda abierto con el motivo a la vista.
                            this.error = res.message ?? 'No se pudo actualizar el usuario.';
                        }
                    } catch (e) {
                        this.error = 'Hubo un problema al actualizar el usuario.';
                    } finally {
                        this.saving = false;
                    }
                },

                async toggleState(user) {
                    const { isConfirmed } = await window.Swal.fire({
                        title: '¿Estás seguro?',
                        text: '¿Quieres cambiar el estado del usuario?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, cambiar estado',
                        cancelButtonText: 'Cancelar',
                    });
                    if (!isConfirmed) return;

                    try {
                        const res = await window.api(this.urls.changeStatus.replace('__ID__', user.id), { method: 'POST' });
                        // Ojo: Boolean("0") es true; el JSON puede traer el tinyint como string.
                        user.state = Number(res.user.state) === 1;
                    } catch (e) {
                        window.Swal.fire({ icon: 'error', title: 'Error',
                                           text: 'No se pudo cambiar el estado del usuario' });
                    }
                },
            }));
        });
    </script>
@endsection
