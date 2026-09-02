@extends('layouts.tw.app')

@section('title', 'Cambiar contraseña')

@section('content_header')
    <h1>Cambiar contraseña</h1>
@endsection

@section('subtitle', 'Se aplica al guardar y tendrás que usarla la próxima vez que entres.')

@section('content')
    <div class="mx-auto max-w-lg" x-data="cambioClave()">
        <form method="POST" action="{{ route('updatePassword', $user) }}" autocomplete="off"
              @submit="if (!valido()) $event.preventDefault()"
              class="tw-card overflow-hidden">
            @csrf
            @method('PUT')

            <div class="flex items-center gap-3 border-b border-slate-200/80 px-5 py-4 dark:border-slate-700/60">
                <span class="tw-chip chip-amber"><i class="fas fa-key"></i></span>
                <div class="min-w-0">
                    <h2 class="tw-card-title truncate">{{ $user->name }}</h2>
                    <p class="tw-card-subtitle truncate">{{ $user->email }}</p>
                </div>
            </div>

            <div class="space-y-4 p-5">
                @foreach ([
                    ['campo' => 'nueva',     'name' => 'new_password',  'etiqueta' => 'Nueva contraseña'],
                    ['campo' => 'confirmar', 'name' => 'conf_password', 'etiqueta' => 'Confirmar contraseña'],
                ] as $c)
                    <div>
                        <label class="tw-label" for="clave-{{ $c['campo'] }}">{{ $c['etiqueta'] }}</label>
                        <div class="relative">
                            {{-- El ojo va dentro del campo, no colocado por encima con
                                 un translate a ojo como en la versión anterior. --}}
                            <input :type="ver.{{ $c['campo'] }} ? 'text' : 'password'"
                                   name="{{ $c['name'] }}" id="clave-{{ $c['campo'] }}"
                                   class="tw-input pr-11" :class="claseCampo('{{ $c['campo'] }}')"
                                   x-model="form.{{ $c['campo'] }}" required>
                            <button type="button"
                                    @click="ver.{{ $c['campo'] }} = !ver.{{ $c['campo'] }}"
                                    class="absolute right-1.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center
                                           justify-center rounded-lg text-slate-400 hover:bg-slate-100
                                           hover:text-slate-600 dark:hover:bg-slate-700 dark:hover:text-slate-200"
                                    :aria-label="ver.{{ $c['campo'] }} ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                                <i class="fas" :class="ver.{{ $c['campo'] }} ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                @endforeach

                <p class="tw-hint">
                    <i class="fas fa-circle-info"></i> Mínimo 8 caracteres.
                </p>

                <p x-show="error" x-cloak
                   class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                          dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
                   x-text="error"></p>
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200/80 px-5 py-4
                        dark:border-slate-700/60">
                <a href="{{ route('profile.show') }}" class="tw-btn-secondary">Cancelar</a>
                <button type="submit" class="tw-btn-primary">
                    <i class="fas fa-floppy-disk"></i> Cambiar contraseña
                </button>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('cambioClave', () => ({
                form: { nueva: '', confirmar: '' },
                ver: { nueva: false, confirmar: false },
                error: '',
                invalidos: [],

                claseCampo(campo) {
                    return this.invalidos.includes(campo)
                        ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                        : '';
                },

                /* Las mismas reglas que aplica el servidor. Comprobarlas aquí
                   evita el viaje y la recarga: hasta ahora el motivo llegaba como
                   un mensaje de sesión después de repintar la página entera. */
                valido() {
                    this.invalidos = [];
                    this.error = '';

                    const { nueva, confirmar } = this.form;

                    if (!nueva.trim() || !confirmar.trim()) {
                        this.invalidos = [!nueva.trim() ? 'nueva' : null,
                                          !confirmar.trim() ? 'confirmar' : null].filter(Boolean);
                        this.error = 'Escribe la contraseña nueva y su confirmación.';
                        return false;
                    }
                    if (nueva.length < 8) {
                        this.invalidos = ['nueva'];
                        this.error = 'La contraseña debe ser de mínimo 8 caracteres.';
                        return false;
                    }
                    if (nueva !== confirmar) {
                        this.invalidos = ['nueva', 'confirmar'];
                        this.error = 'Las contraseñas no coinciden.';
                        return false;
                    }
                    return true;
                },
            }));
        });
    </script>
@endsection
