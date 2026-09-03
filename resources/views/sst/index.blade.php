@extends('layouts.tw.app')

@section('title', 'Preoperacional')

@section('content_header')
    <h1>Preoperacional</h1>
@endsection

@section('subtitle', 'Exporta a GDW los preoperacionales de un rango de fechas.')

@section('content')
    <div x-data="preoperacional({ url: '{{ route('sst.exportar') }}' })"
         class="max-w-2xl space-y-4 2xl:space-y-6">

        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-sky"><i class="fas fa-helmet-safety"></i></span>
                    <div>
                        <h2 class="tw-card-title">Rango de fechas</h2>
                        <p class="tw-card-subtitle">Ambas fechas son obligatorias.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="tw-label" for="fecha_inicio">Desde</label>
                        <input type="date" id="fecha_inicio" class="tw-input"
                               :class="claseCampo('fecha_inicio')"
                               x-model="fechaInicio" :max="fechaFin || null">
                    </div>
                    <div>
                        <label class="tw-label" for="fecha_fin">Hasta</label>
                        <input type="date" id="fecha_fin" class="tw-input"
                               :class="claseCampo('fecha_fin')"
                               x-model="fechaFin" :min="fechaInicio || null">
                    </div>
                </div>

                <div x-show="error" x-cloak x-transition.opacity
                     class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3
                            text-sm text-red-800
                            dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
                     role="alert">
                    <i class="fas fa-circle-exclamation mt-0.5"></i>
                    <span x-text="error"></span>
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/60">
                <button type="button" class="tw-btn-primary" @click="exportar()" :disabled="exportando">
                    <i class="fas" :class="exportando ? 'fa-spinner fa-spin' : 'fa-file-excel'"></i>
                    Exportar a GDW
                </button>
            </div>
        </section>
    </div>
@endsection

@section('js')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('preoperacional', ({ url }) => ({
                url,
                fechaInicio: '',
                fechaFin: '',
                exportando: false,
                error: '',
                invalidos: [],

                claseCampo(campo) {
                    return this.invalidos.includes(campo)
                        ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                        : '';
                },

                /* Las mismas tres reglas que valida el servidor, para no ir y
                   volver por un campo vacío. Si aun así responde 422, se muestra
                   su mensaje tal cual. */
                revisar() {
                    this.invalidos = [];
                    if (!this.fechaInicio) this.invalidos.push('fecha_inicio');
                    if (!this.fechaFin) this.invalidos.push('fecha_fin');

                    if (this.invalidos.length) return 'Indica la fecha de inicio y la de fin.';
                    if (this.fechaInicio > this.fechaFin) {
                        this.invalidos = ['fecha_inicio', 'fecha_fin'];
                        return 'La fecha de inicio no puede ser mayor que la fecha final.';
                    }
                    return '';
                },

                async exportar() {
                    this.error = this.revisar();
                    if (this.error) return;

                    this.exportando = true;
                    try {
                        const r = await window.api(this.url, {
                            method: 'POST',
                            body: { fecha_inicio: this.fechaInicio, fecha_fin: this.fechaFin },
                        });
                        if (r?.url) window.location.href = r.url;
                    } catch (e) {
                        this.error = e?.data?.error ?? 'No se pudo generar el archivo.';
                    } finally {
                        this.exportando = false;
                    }
                },
            }));
        });
    </script>
@endsection
