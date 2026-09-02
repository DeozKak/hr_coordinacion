{{-- Modal de edición. Vive dentro del x-data de usersTable, así que lee su
     estado directamente. Usa el componente compartido: el modal artesanal que
     había aquí no difuminaba el fondo y su x-trap.noscroll se peleaba con la
     capa de alertas, que es lo que dejaba la ventana atascada. --}}
<x-modal show="modal === 'editar'" close="closeEdit()" size="max-w-2xl"
         icon="fa-user-pen" tint="blue" title="Editar usuario"
         subtitle="Datos, rol y permisos">

    <div class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-4">

        {{-- Los avisos van dentro del modal: así corregir no obliga a pasar por
             una ventana de alerta encima de otra. --}}
        <template x-if="error">
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                        dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
                 x-text="error"></div>
        </template>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="editName" class="tw-label">Nombre</label>
                <input id="editName" type="text" x-model="form.name" class="tw-input"
                       :class="claseCampo('name')" required>
            </div>
            <div>
                <label for="editEmail" class="tw-label">Email</label>
                <input id="editEmail" type="email" x-model="form.email" class="tw-input"
                       :class="claseCampo('email')" required>
            </div>
            <div>
                <label for="editTypeId" class="tw-label">Tipo de identificación</label>
                <input id="editTypeId" type="text" x-model="form.type_id" class="tw-input" disabled>
            </div>
            <div>
                <label for="editIdentification" class="tw-label">Identificación</label>
                <input id="editIdentification" type="text" x-model="form.identification" class="tw-input" disabled>
            </div>
        </div>

        <div>
            <label for="editRole" class="tw-label">Rol</label>
            <select id="editRole" x-model="form.role" class="tw-select">
                <template x-for="role in roles" :key="role">
                    <option :value="role" x-text="role"></option>
                </template>
            </select>
        </div>

        {{-- Doble lista de permisos --}}
        <div>
            <span class="tw-label">Permisos</span>
            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                <div>
                    <label for="availablePerms" class="mb-1 block text-xs text-slate-500">Disponibles</label>
                    <select id="availablePerms" multiple size="8" x-model="availableSel" class="tw-input">
                        <template x-for="p in available" :key="p">
                            <option :value="p" x-text="p"></option>
                        </template>
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <button type="button"
                            class="tw-btn-secondary px-2 py-1"
                            :disabled="!availableSel.length"
                            @click="move('available', 'assigned', 'availableSel')"
                            aria-label="Asignar permisos seleccionados">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="button"
                            class="tw-btn-secondary px-2 py-1"
                            :disabled="!assignedSel.length"
                            @click="move('assigned', 'available', 'assignedSel')"
                            aria-label="Quitar permisos seleccionados">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                </div>

                <div>
                    <label for="assignedPerms" class="mb-1 block text-xs text-slate-500">Asignados</label>
                    <select id="assignedPerms" multiple size="8" x-model="assignedSel" class="tw-input">
                        <template x-for="p in assigned" :key="p">
                            <option :value="p" x-text="p"></option>
                        </template>
                    </select>
                </div>
            </div>

            <p x-show="loadingPerms" class="mt-2 text-xs text-slate-500">
                <i class="fas fa-circle-notch fa-spin"></i> Cargando permisos…
            </p>
        </div>

        {{-- Cambio de contraseña.
             Sin x-collapse a propósito. Ese plugin sustituye la maquinaria de
             transición del elemento por la suya, que anima la altura y termina
             con `transitionend`. Al cerrar el modal se ocultan a la vez el panel
             (transición del componente) y este bloque (transición del collapse):
             el ancestro llega a `display:none` primero, el `transitionend` del
             hijo no llega nunca y su transición queda a medias. Alpine ve una
             transición en curso y la revierte, que es lo que hacía reaparecer la
             ventana y la dejaba sin responder, porque el estado ya decía cerrada.
             Era el único modal de la aplicación con un collapse dentro. --}}
        <div x-show="changingPassword" x-cloak>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                <p class="tw-hint mb-3 mt-0">Mínimo 8 caracteres. Se aplica al guardar.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach (['nueva' => 'Nueva contraseña', 'confirmar' => 'Confirmar contraseña'] as $field => $label)
                        <div>
                            <label for="pw-{{ $field }}" class="tw-label">{{ $label }}</label>
                            <div class="relative">
                                <input id="pw-{{ $field }}"
                                       :type="password.show{{ ucfirst($field) }} ? 'text' : 'password'"
                                       x-model="password.{{ $field }}"
                                       class="tw-input pr-10"
                                       :class="claseCampo('{{ $field }}')"
                                       autocomplete="new-password">
                                <button type="button"
                                        @click="password.show{{ ucfirst($field) }} = !password.show{{ ucfirst($field) }}"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600"
                                        :aria-label="password.show{{ ucfirst($field) }} ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                                    <i class="fas" :class="password.show{{ ucfirst($field) }} ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" @click="closeEdit()" class="tw-btn-secondary">Cancelar</button>

        <button type="button"
                @click="togglePassword()"
                class="tw-btn-secondary"
                x-text="changingPassword ? 'Cancelar cambio' : 'Cambiar contraseña'"></button>

        <button type="button" @click="save()" :disabled="saving" class="tw-btn-primary">
            <i class="fas fa-circle-notch fa-spin" x-show="saving" x-cloak></i>
            <span x-text="saving ? 'Guardando…' : 'Guardar'"></span>
        </button>
    </x-slot:footer>
</x-modal>
