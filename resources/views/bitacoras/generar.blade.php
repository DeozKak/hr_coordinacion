@extends('layouts.tw.app')

@section('title', 'Bitácoras')
@section('content_header')
    <h1>Bitácoras</h1>
@endsection
@section('subtitle', 'Carga y generación de bitácoras.')

@section('content')
<div x-data="generarBitacora({
        urlDiaria: '{{ route('bitacoras.diaria') }}',
     })"
     class="grid gap-4 2xl:gap-6 md:grid-cols-2">

    @unlessrole('Supervisor')
        {{-- Bitácora Todos (no suma producción) --}}
        <section class="tw-card flex flex-col">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-sky"><i class="fas fa-file-lines"></i></span>
                    <div>
                        <h2 class="tw-card-title">Bitácora Todos</h2>
                        <p class="tw-card-subtitle">No suma producción</p>
                    </div>
                </div>
            </div>

            <div class="tw-card-body flex flex-1 flex-col gap-4">
                <div>
                    <span class="tw-label">Seleccione Bitácora</span>
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-slate-300 px-4 py-4
                                  transition hover:border-brand-400 hover:bg-brand-50/40
                                  dark:border-slate-600 dark:hover:bg-slate-700/40">
                        <span class="tw-chip chip-sky"><i class="fas fa-file-arrow-up"></i></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-slate-700 dark:text-slate-200"
                                  x-text="archivoDiaria || 'Seleccionar archivo…'"></span>
                            <span class="block text-xs text-slate-400">Excel o CSV</span>
                        </span>
                        <input type="file" class="sr-only" x-ref="archivoDiaria"
                               @change="archivoDiaria = $event.target.files[0]?.name ?? ''">
                    </label>
                </div>

                {{-- Mensaje de error del procesamiento --}}
                <div x-show="error" x-cloak x-transition
                     class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800
                            dark:border-red-800 dark:bg-red-950 dark:text-red-200"
                     role="alert" x-html="error"></div>

                <div class="mt-auto pt-2">
                    <button type="button" @click="procesar()" :disabled="procesando || !archivoDiaria"
                            class="tw-btn-primary w-full justify-center py-2.5">
                        <i class="fas" :class="procesando ? 'fa-spinner fa-spin' : 'fa-gears'"></i>
                        <span x-text="procesando ? 'Procesando…' : 'Procesar'"></span>
                    </button>
                </div>
            </div>
        </section>
    @endunlessrole

    {{-- Subir archivos --}}
    <section class="tw-card flex flex-col">
        <div class="tw-card-header">
            <div class="flex items-center gap-3">
                <span class="tw-chip chip-emerald"><i class="fas fa-cloud-arrow-up"></i></span>
                <div>
                    <h2 class="tw-card-title">Subir Archivos</h2>
                    <p class="tw-card-subtitle">Genera la bitácora del supervisor</p>
                </div>
            </div>
        </div>

        <form action="{{ route('bitacoras.generar') }}" method="POST" enctype="multipart/form-data"
              class="tw-card-body flex flex-1 flex-col gap-4"
              x-data="{ archivo: '', enviando: false }" @submit="enviando = true">
            @csrf

            @role('Supervisor')
                <input type="hidden" name="supervisor" value="{{ $supervisores->id }}">
                <div>
                    <span class="tw-label">Supervisor</span>
                    <select class="tw-select" disabled>
                        <option selected>{{ $supervisores->name }}</option>
                    </select>
                </div>
            @else
                <div>
                    <label for="supervisor" class="tw-label">Supervisor</label>
                    <select name="supervisor" id="supervisor"
                            @class(['tw-select', 'border-red-400' => $errors->has('supervisor')])>
                        <option value="">Seleccione Supervisor</option>
                        @foreach ($supervisores as $supervisor)
                            <option value="{{ $supervisor->id }}" @selected(old('supervisor') == $supervisor->id)>
                                {{ $supervisor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supervisor')
                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endrole

            <div>
                <span class="tw-label">Seleccione Bitácora</span>
                <label @class([
                        'flex cursor-pointer items-center gap-3 rounded-xl border border-dashed px-4 py-4 transition',
                        'hover:border-brand-400 hover:bg-brand-50/40 dark:hover:bg-slate-700/40',
                        'border-red-400' => $errors->has('archivo'),
                        'border-slate-300 dark:border-slate-600' => ! $errors->has('archivo'),
                    ])>
                    <span class="tw-chip chip-emerald"><i class="fas fa-file-arrow-up"></i></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-slate-700 dark:text-slate-200"
                              x-text="archivo || 'Seleccionar archivo…'"></span>
                        <span class="block text-xs text-slate-400">Excel o CSV</span>
                    </span>
                    <input type="file" name="archivo" class="sr-only"
                           @change="archivo = $event.target.files[0]?.name ?? ''">
                </label>
                @error('archivo')
                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-auto pt-2">
                <button type="submit" :disabled="enviando" class="tw-btn-primary w-full justify-center py-2.5">
                    <i class="fas" :class="enviando ? 'fa-spinner fa-spin' : 'fa-upload'"></i>
                    <span x-text="enviando ? 'Subiendo…' : 'Subir Archivo'"></span>
                </button>
            </div>
        </form>
    </section>
</div>
@endsection

@section('js')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('generarBitacora', ({ urlDiaria }) => ({
        archivoDiaria: '',
        procesando: false,
        error: '',

        async procesar() {
            const input = this.$refs.archivoDiaria;
            if (!input.files.length) return;

            this.procesando = true;
            this.error = '';

            const datos = new FormData();
            datos.append('archivo', input.files[0]);

            try {
                const res = await window.api(urlDiaria, { method: 'POST', body: datos });
                if (res.url) window.location.href = res.url;
            } catch (e) {
                // El controlador responde { error: "..." } con estado 4xx/5xx.
                this.error = e.data?.error ?? 'No se pudo procesar el archivo.';
            } finally {
                this.procesando = false;
            }
        },
    }));
});
</script>

@if (session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({ title: 'Error', text: @js(session('error')), icon: 'error' });
        });
    </script>
    @php session()->forget('error'); @endphp
@endif

@if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({ title: 'Éxito', text: @js(session('success')), icon: 'success' });
        });
    </script>
@endif

{{-- El aviso y $temp van juntos: el controlador sólo manda la bitácora a medias
     cuando existe, y el bloque la usa para armar las dos rutas. Comprobar sólo
     la sesión no bastaba —si el aviso venía de otro sitio, la vista reventaba
     con "Undefined variable $temp"—, así que se exigen las dos cosas. --}}
@if (session('warning') && isset($temp))
    {{-- Flujo de bitácora temporal: restaurar, descartar o mantener cambios. --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const urlRestaurar = @js(route('bitacoras.restaurar', ['id' => $temp]));
            const urlBorrar    = @js(route('bitacoras.borrar', ['id' => $temp]));

            Swal.fire({
                title: @js(session('warning')),
                showDenyButton: true,
                showCancelButton: false,
                allowOutsideClick: false,
                confirmButtonText: 'Sí',
                denyButtonText: 'No',
            }).then((r) => {
                if (r.isConfirmed) { window.location.href = urlRestaurar; return; }
                if (!r.isDenied) return;

                Swal.fire({
                    icon: 'warning',
                    title: '¡Se perderán los cambios!',
                    allowOutsideClick: false,
                    showDenyButton: true,
                    confirmButtonText: 'Quiero generar una bitácora nueva',
                    denyButtonText: 'Mantener cambios',
                }).then(async (r2) => {
                    if (r2.isConfirmed) {
                        try { await window.api(urlBorrar, { method: 'POST' }); }
                        catch (e) { console.error(e); }
                    }
                    if (r2.isDenied) window.location.href = urlRestaurar;
                });
            });
        });
    </script>
@endif
@endsection
