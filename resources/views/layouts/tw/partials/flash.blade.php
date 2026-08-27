@php
    /* Clases completas y literales: el escáner de Tailwind necesita verlas tal cual. */
    $styles = [
        'success' => 'border-green-300 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200',
        'error'   => 'border-red-300 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200',
        'warning' => 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200',
        'info'    => 'border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200',
    ];
@endphp

@foreach ($styles as $key => $classes)
    @if (session($key))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 6000)"
             x-transition
             class="mb-4 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ $classes }}"
             role="alert">
            <span class="flex-1">{{ session($key) }}</span>
            <button type="button" @click="show = false" aria-label="Cerrar">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
    @endif
@endforeach

@if (isset($errors) && $errors->any())
    <div class="mb-4 rounded-lg border px-4 py-3 text-sm {{ $styles['error'] }}" role="alert">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
