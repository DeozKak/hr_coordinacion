@extends('layouts.tw.app')

@section('title', 'Mi perfil')

@section('content_header')
    <h1>Mi perfil</h1>
@endsection

@section('subtitle', 'Tus datos de acceso a Seguimiento Operación.')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6"
         x-data="perfil({
            nombre: @js($user->name),
            email: @js($user->email),
         })">

        {{-- ============================= IDENTIDAD ============================ --}}
        <section class="tw-card overflow-hidden">
            <div class="flex items-center gap-4 border-b border-slate-200/80 bg-slate-50/60 px-5 py-5
                        dark:border-slate-700/60 dark:bg-slate-900/40">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-600
                             text-xl font-bold text-white">
                    {{ Str::of($user->name)->substr(0, 1)->upper() }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-slate-900 dark:text-white">{{ $user->name }}</p>
                    <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
                </div>
                @if ($currentRole)
                    <span class="tw-badge chip-blue ml-auto shrink-0">{{ $currentRole->name }}</span>
                @endif
            </div>

            {{-- El formulario guarda lo único que el servidor persiste del perfil:
                 nombre y correo. El resto se enseña, pero no se toca desde aquí. --}}
            <form method="POST" action="{{ route('update', $user) }}" autocomplete="off"
                  @submit="if (!valido()) $event.preventDefault()">
                @csrf
                @method('PUT')

                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="tw-label" for="name">Nombre</label>
                        <input type="text" name="name" id="name" class="tw-input" :class="claseCampo('nombre')"
                               x-model="form.nombre" maxlength="255" required>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="tw-label" for="email">Correo</label>
                        <input type="email" name="email" id="email" class="tw-input" :class="claseCampo('email')"
                               x-model="form.email" maxlength="255" required>
                    </div>

                    <div>
                        <label class="tw-label" for="type_id">Tipo de identificación</label>
                        <input type="text" id="type_id" class="tw-input" value="{{ $user->type_id }}" disabled>
                    </div>

                    <div>
                        <label class="tw-label" for="identification">Identificación</label>
                        <input type="text" id="identification" class="tw-input"
                               value="{{ $user->identification }}" disabled>
                    </div>

                    <p class="tw-hint sm:col-span-2">
                        <i class="fas fa-lock"></i>
                        La identificación y el rol los cambia un administrador desde gestión de usuarios.
                    </p>

                    <p x-show="error" x-cloak
                       class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                              dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200 sm:col-span-2"
                       x-text="error"></p>
                </div>

                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200/80 px-5 py-4
                            dark:border-slate-700/60">
                    <a href="{{ route('home') }}" class="tw-btn-secondary">Cancelar</a>
                    <button type="submit" class="tw-btn-primary" :disabled="!huboCambios">
                        <i class="fas fa-floppy-disk"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </section>

        {{-- ============================= SEGURIDAD =========================== --}}
        <section class="tw-card">
            <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-amber"><i class="fas fa-key"></i></span>
                    <div>
                        <h2 class="tw-card-title">Contraseña</h2>
                        <p class="tw-card-subtitle">Mínimo 8 caracteres.</p>
                    </div>
                </div>
                <a href="{{ route('changePassword', $user) }}" class="tw-btn-secondary">
                    <i class="fas fa-key"></i> Cambiar contraseña
                </a>
            </div>
        </section>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('perfil', (original) => ({
                original,
                form: { ...original },
                error: '',
                invalidos: [],

                /* El botón se apaga mientras no haya nada que guardar: el
                   servidor asigna y guarda sin comprobar nada, así que un envío
                   de más es una escritura de más. */
                get huboCambios() {
                    return this.form.nombre !== this.original.nombre
                        || this.form.email !== this.original.email;
                },

                claseCampo(campo) {
                    return this.invalidos.includes(campo)
                        ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                        : '';
                },

                /* Se comprueba aquí porque el servidor no valida este formulario:
                   guardaría un nombre vacío o un correo sin arroba tal cual. */
                valido() {
                    this.invalidos = [];
                    this.error = '';

                    if (!this.form.nombre.trim()) this.invalidos.push('nombre');
                    if (!this.form.email.trim()) this.invalidos.push('email');
                    if (this.invalidos.length) {
                        this.error = 'El nombre y el correo son obligatorios.';
                        return false;
                    }

                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email.trim())) {
                        this.invalidos = ['email'];
                        this.error = 'El correo no tiene un formato válido.';
                        return false;
                    }
                    return true;
                },
            }));
        });
    </script>
@endsection
