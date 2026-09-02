{{-- Porcentaje: admite decimales, y el símbolo vive fuera del valor. --}}
<div class="relative">
    <input type="text" id="{{ $id }}" class="tw-input pr-8 text-right tabular-nums"
           inputmode="decimal" autocomplete="off"
           :class="claseCampo('{{ $campo }}')"
           :value="form.{{ $campo }}"
           @input="form.{{ $campo }} = soloDecimal($event.target.value)">
    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">%</span>
</div>
