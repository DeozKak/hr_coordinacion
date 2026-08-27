{{-- Modal de edición. Vive dentro del x-data de usersTable, así que lee su estado directamente. --}}
<div x-show="editing"
     x-cloak
     @keydown.escape.window="closeEdit()"
     class="fixed inset-0 z-50 overflow-y-auto"
     role="dialog"
     aria-modal="true"
     aria-labelledby="editUserTitle">

    <div x-show="editing" x-transition.opacity class="fixed inset-0 bg-slate-900/60"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="editing"
             x-transition.scale.95
             @click.outside="closeEdit()"
             x-trap.noscroll="editing"
             class="relative w-full max-w-2xl rounded-lg bg-white shadow-xl dark:bg-slate-800">

            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                <h2 id="editUserTitle" class="text-lg font-semibold">Editar Usuario</h2>
                <button type="button" @click="closeEdit()" class="rounded p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700" aria-label="Cerrar">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-4">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="editName" class="tw-label">Nombre</label>
                        <input id="editName" type="text" x-model="form.name" class="tw-input" required>
                    </div>
                    <div>
                        <label for="editEmail" class="tw-label">Email</label>
                        <input id="editEmail" type="email" x-model="form.email" class="tw-input" required>
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

                {{-- Cambio de contraseña --}}
                <div x-show="changingPassword" x-collapse>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach (['nueva' => 'Nueva contraseña', 'confirmar' => 'Confirmar contraseña'] as $field => $label)
                            <div>
                                <label for="pw-{{ $field }}" class="tw-label">{{ $label }}</label>
                                <div class="relative">
                                    <input id="pw-{{ $field }}"
                                           :type="password.show{{ ucfirst($field) }} ? 'text' : 'password'"
                                           x-model="password.{{ $field }}"
                                           class="tw-input pr-10"
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

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                <button type="button" @click="closeEdit()" class="tw-btn-secondary">Cancelar</button>

                <button type="button"
                        @click="changingPassword ? resetPassword() : (changingPassword = true)"
                        class="tw-btn-secondary"
                        x-text="changingPassword ? 'Cancelar cambio' : 'Cambiar Contraseña'"></button>

                <button type="button" @click="save()" :disabled="saving" class="tw-btn-primary">
                    <i class="fas fa-circle-notch fa-spin" x-show="saving" x-cloak></i>
                    <span x-text="saving ? 'Guardando…' : 'Guardar'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
