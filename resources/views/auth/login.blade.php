@extends('layouts.tw.auth')

@section('auth_header', 'Inicia sesión')
@section('auth_intro', 'Ingresa tus credenciales para acceder al panel.')

@section('auth_body')
    <form action="{{ route('login') }}" method="post" autocomplete="off">
        @csrf

        <x-auth-input name="email" type="email" label="Correo electrónico"
                      placeholder="nombre@eyc.com.co" icon="fas fa-envelope" autofocus />

        <x-auth-input name="password" label="Contraseña"
                      placeholder="••••••••" icon="fas fa-lock" toggle />

        <div class="mb-6 flex items-center justify-between gap-3">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600"
                   title="Mantenerme autenticado hasta cerrar la sesión manualmente">
                <input type="checkbox" name="remember" id="remember"
                       class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Recordarme
            </label>

            <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 hover:underline">
                ¿Olvidaste tu contraseña?
            </a>
        </div>

        <button type="submit" class="tw-btn-primary w-full py-3 text-[15px]">
            Acceder <i class="fas fa-arrow-right text-xs"></i>
        </button>
    </form>
@endsection

@section('auth_footer')
    ¿Problemas para ingresar? Contacta al administrador del sistema.
@endsection

@section('js')
    @if (session('error') || session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire(@js([
                    'title' => session('error') ? '' : '¡Listo!',
                    'text'  => session('error') ?: session('success'),
                    'icon'  => session('error') ? 'warning' : 'success',
                ]));
            });
        </script>
    @endif
@endsection
