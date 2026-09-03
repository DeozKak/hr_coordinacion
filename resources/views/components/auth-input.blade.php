@props([
    'name',
    'type' => 'text',
    'label' => null,
    'icon' => null,
    'value' => null,
    'toggle' => false,   // muestra el ojo para ver/ocultar la contraseña
])

<div class="mb-5" @if ($toggle) x-data="{ show: false }" @endif>
    @if ($label)
        <label for="{{ $name }}" class="tw-label">{{ $label }}</label>
    @endif

    <div class="relative">
        @if ($icon)
            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                <i class="{{ $icon }} text-[0.9375rem]"></i>
            </span>
        @endif

        <input id="{{ $name }}"
               name="{{ $name }}"
               @if ($toggle) :type="show ? 'text' : 'password'" @else type="{{ $type }}" @endif
               @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
               {{ $attributes->class([
                    'tw-input',
                    'pl-10' => $icon,
                    'pr-11' => $toggle,
                    'border-red-400 focus:border-red-500 focus:ring-red-500/10' => $errors->has($name),
               ]) }}>

        @if ($toggle)
            <button type="button" @click="show = !show" tabindex="-1"
                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 transition hover:text-slate-600"
                    :aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                <i class="fas text-[0.9375rem]" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
            </button>
        @endif
    </div>

    @error($name)
        <p class="mt-1.5 flex items-center gap-1.5 text-sm text-red-600">
            <i class="fas fa-circle-exclamation text-xs"></i> {{ $message }}
        </p>
    @enderror
</div>
