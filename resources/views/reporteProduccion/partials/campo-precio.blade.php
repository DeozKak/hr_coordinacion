{{-- Campo de dinero: el usuario teclea dígitos y ve el importe con separador de
     miles; el estado guarda el número, no el texto. --}}
<div class="relative">
    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">$</span>
    <input type="text" id="{{ $id }}" class="tw-input pl-7 text-right tabular-nums"
           inputmode="numeric" autocomplete="off"
           :class="claseCampo('{{ $campo }}')"
           :value="moneda(form.{{ $campo }})"
           @input="form.{{ $campo }} = soloDigitos($event.target.value)"
           @blur="$event.target.value = moneda(form.{{ $campo }})">
</div>
