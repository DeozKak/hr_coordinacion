@extends('layouts.tw.auth')

@section('auth_header', 'Nueva contraseña')
@section('auth_intro', 'Define una contraseña de al menos 8 caracteres.')

@section('auth_body')
    <form action="{{ route('password.update') }}" method="post">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-auth-input name="email" type="email" label="Correo electrónico"
                      icon="fas fa-envelope" :value="$email ?? null" autofocus />

        <x-auth-input name="password" label="Nueva contraseña"
                      placeholder="Mínimo 8 caracteres" icon="fas fa-lock" toggle />

        <x-auth-input name="password_confirmation" label="Confirmar contraseña"
                      placeholder="Repite la contraseña" icon="fas fa-lock" toggle />

        <button type="submit" class="tw-btn-primary w-full py-3 text-[15px]">
            <i class="fas fa-rotate text-xs"></i> Restablecer contraseña
        </button>
    </form>
@endsection

@section('auth_footer')
    <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">
        <i class="fas fa-arrow-left text-xs"></i> Volver al inicio de sesión
    </a>
@endsection
