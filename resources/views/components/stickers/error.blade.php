@props(['message'])

{{-- Bloque de error inline: sustituye a los <div class="alert alert-danger d-none"> del original. --}}
<div x-show="{{ $message }}" x-cloak x-transition
     {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3
                                        text-sm text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/40
                                        dark:text-rose-200']) }}>
    <i class="fas fa-circle-exclamation mt-0.5 shrink-0"></i>
    <span x-text="{{ $message }}"></span>
</div>
