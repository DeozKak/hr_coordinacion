@props(['message'])

{{-- Bloque de error inline: sustituye a los alert-modern del diseño anterior.
     Acepta un string o una lista de mensajes de validación. --}}
<div x-show="{{ $message }} && (Array.isArray({{ $message }}) ? {{ $message }}.length : true)" x-cloak x-transition
     {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3
                                        text-sm text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/40
                                        dark:text-rose-200']) }}>
    <i class="fas fa-circle-exclamation mt-0.5 shrink-0"></i>
    <div class="min-w-0">
        <template x-if="!Array.isArray({{ $message }})">
            <span x-text="{{ $message }}"></span>
        </template>
        <template x-if="Array.isArray({{ $message }})">
            <ul class="list-inside list-disc space-y-0.5">
                <template x-for="(m, i) in {{ $message }}" :key="i"><li x-text="m"></li></template>
            </ul>
        </template>
    </div>
</div>
