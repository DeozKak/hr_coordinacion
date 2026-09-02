@extends('layouts.tw.auth')

@section('auth_header', 'Crear cuenta')
@section('auth_intro', 'Registra un nuevo usuario en el sistema.')

@section('auth_body')
    <form action="{{ route('register') }}" method="post" autocomplete="off"
          x-data="{ identification: '{{ old('identification') }}' }">
        @csrf

        <x-auth-input name="name" label="Nombre completo"
                      placeholder="Nombre y apellidos" icon="fas fa-user" autofocus />

        <x-auth-input name="email" type="email" label="Correo electrónico"
                      placeholder="nombre@eyc.com.co" icon="fas fa-envelope" />

        <div class="mb-5 grid grid-cols-[7.5rem_1fr] gap-3">
            <div>
                <label for="type_id" class="tw-label">Tipo</label>
                <select id="type_id" name="type_id"
                        @class(['tw-select', 'border-red-400' => $errors->has('type_id')])>
                    <option value="" @selected(! old('type_id'))>—</option>
                    <option value="CC" @selected(old('type_id') === 'CC')>CC</option>
                    <option value="CE" @selected(old('type_id') === 'CE')>CE</option>
                </select>
            </div>
            <div>
                <label for="identification" class="tw-label">Identificación</label>
                {{-- Sólo dígitos, máx. 13: la restricción vive en el x-model. --}}
                <input id="identification" name="identification" inputmode="numeric"
                       placeholder="No. de documento"
                       x-model="identification"
                       x-effect="identification = identification.replace(/\D/g, '').slice(0, 13)"
                       @class(['tw-input', 'border-red-400' => $errors->has('identification')])>
            </div>
        </div>

        @error('type_id') <p class="-mt-3 mb-4 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('identification') <p class="-mt-3 mb-4 text-sm text-red-600">{{ $message }}</p> @enderror

        <x-auth-input name="password" label="Contraseña"
                      placeholder="Mínimo 8 caracteres" icon="fas fa-lock" toggle />

        <x-auth-input name="password_confirmation" label="Confirmar contraseña"
                      placeholder="Repite la contraseña" icon="fas fa-lock" toggle />

        <button type="submit" class="tw-btn-primary w-full py-3 text-[15px]">
            <i class="fas fa-user-plus text-xs"></i> Registrarse
        </button>
    </form>
@endsection

@section('auth_footer')
    ¿Ya tienes cuenta?
    <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">Inicia sesión</a>
@endsection
