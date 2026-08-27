@extends('layouts.tw.auth')

@section('auth_header', 'Confirma tu contraseña')
@section('auth_intro', 'Por seguridad, vuelve a ingresarla para continuar.')

@section('auth_body')
    <form action="{{ route('password.confirm') }}" method="post">
        @csrf
        <x-auth-input name="password" label="Contraseña"
                      placeholder="••••••••" icon="fas fa-lock" toggle autofocus />

        <button type="submit" class="tw-btn-primary w-full py-3 text-[15px]">
            <i class="fas fa-lock-open text-xs"></i> Confirmar
        </button>
    </form>
@endsection

@section('auth_footer')
    <a href="{{ route('password.request') }}" class="font-semibold text-brand-600 hover:underline">
        ¿Olvidaste tu contraseña?
    </a>
@endsection
