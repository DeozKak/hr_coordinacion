@extends('adminlte::auth.register')
@section('auth_body')

<form action="{{ route('register') }}" method="post" autocomplete="off">
    @csrf
    <link rel="stylesheet" href="{{asset('css/auth/register.css')}}">
    <div class="input-group mb-3">
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Nombre completo" autofocus>

        <div class="input-group-append">
            <div class="input-group-text">
                <span class="fas fa-user"></span>
            </div>
        </div>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="input-group mb-3">
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="Email">

        <div class="input-group-append">
            <div class="input-group-text">
                <span class="fas fa-envelope"></span>
            </div>
        </div>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="input-group mb-3">
        <select name="type_id" class="form-select @error('type_id') is-invalid @enderror">
            <option value="" selected>Tipo de identificación</option>
            <option value="CC">CC</option>
            <option value="CE">CE</option>
        </select>

        <div class="input-group-append">
            <div class="input-group-text">
                <span class="fas fa-id-card"></span>
            </div>
        </div>
        @error('type_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="input-group mb-3">
        <input type="number" id="identification" name="identification" class="form-control @error('identification') is-invalid @enderror" placeholder="No de identificación">

        <div class="input-group-append">
            <div class="input-group-text">
                <span class="fas fa-id-card"></span>
            </div>
        </div>
        @error('identification')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="input-group mb-3">
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Contraseña">

        <div class="input-group-append">
            <div class="input-group-text">
                <span class="fas fa-lock"></span>
            </div>
        </div>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="input-group mb-3">
        <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" placeholder="Confirmar la contraseña">

        <div class="input-group-append">
            <div class="input-group-text">
                <span class="fas fa-lock"></span>
            </div>
        </div>
        @error('password_confirmation')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-block btn-flat btn-primary">
        <span class="fas fa-user-plus"></span>
        Registrarse
    </button>

</form>

@section('js')
<script>
    document.getElementById('identification').addEventListener('input', function() {
        if (this.value.length > 13) {
            this.value = this.value.slice(0, 13);
        }
    });
    document.getElementById('identification').addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    document.getElementById('identification').addEventListener('keydown', function(e) {
        if (!/^[0-9]*$/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete') {
            e.preventDefault();
        }
    });
</script>
@endsection
@endsection
