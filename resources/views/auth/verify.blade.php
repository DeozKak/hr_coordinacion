{{-- Antes esta vista era una sola línea que heredaba de la plantilla de
     verificación del paquete retirado. La ruta que la muestra
     (verification.notice) no está registrada — Auth::routes() se llama sin
     ['verify' => true] —, pero la vista se mantiene para no dejar un cabo
     suelto si algún día se activa la verificación por correo. --}}
@extends('layouts.tw.auth')

@section('title', 'Verifica tu correo')

@section('auth_header', 'Verifica tu correo')

@section('auth_intro', 'Te enviamos un enlace de verificación; ábrelo para activar tu cuenta.')

@section('auth_body')
    <div class="space-y-4 text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-2xl
                     text-brand-600 dark:bg-brand-900/40 dark:text-brand-300">
            <i class="fas fa-envelope-open-text"></i>
        </span>

        @if (session('resent'))
            <p class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800
                      dark:border-green-800/60 dark:bg-green-950/40 dark:text-green-200">
                Te acabamos de enviar otro enlace.
            </p>
        @endif

        {{-- El reenvío sólo se ofrece si la ruta existe: verification.resend la
             registra Auth::routes(['verify' => true]), que hoy no está activo, y
             route() sobre una ruta inexistente lanza una excepción. --}}
        @if (\Illuminate\Support\Facades\Route::has('verification.resend'))
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Si no te ha llegado, podemos enviarlo de nuevo.
            </p>
            <form method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <button type="submit" class="tw-btn-primary w-full">
                    <i class="fas fa-paper-plane"></i> Reenviar el correo
                </button>
            </form>
        @else
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Si no te ha llegado, escribe al administrador del sistema.
            </p>
        @endif
    </div>
@endsection
