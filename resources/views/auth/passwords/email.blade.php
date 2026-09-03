@extends('layouts.tw.auth')

@section('auth_header', 'Recuperar contraseña')
@section('auth_intro', 'Te enviaremos un enlace para restablecerla.')

@section('auth_body')
    @if (session('status'))
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="alert">
            <i class="fas fa-circle-check mt-0.5"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form action="{{ route('password.email') }}" method="post">
        @csrf
        <x-auth-input name="email" type="email" label="Correo electrónico"
                      placeholder="nombre@eyc.com.co" icon="fas fa-envelope" autofocus />

        <button type="submit" class="tw-btn-primary w-full py-3 text-[0.9375rem]">
            <i class="fas fa-paper-plane text-xs"></i> Enviar enlace
        </button>
    </form>
@endsection

@section('auth_footer')
    <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">
        <i class="fas fa-arrow-left text-xs"></i> Volver al inicio de sesión
    </a>
@endsection
