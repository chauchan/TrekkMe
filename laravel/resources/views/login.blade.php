@extends('layouts.app')

@section('titulo', 'Ingresar')

@section('contenido')
    <h1>Panel de administración</h1>
    <form class="alta" method="POST" action="/login">
        @csrf
        <label>Correo
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>
        <label>Contraseña
            <input type="password" name="password" required>
        </label>
        <button class="btn">Ingresar</button>
    </form>
@endsection
