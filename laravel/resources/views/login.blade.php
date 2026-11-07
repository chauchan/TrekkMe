@extends('layouts.app')

@section('titulo', 'Ingresar')
@section('body', 'bg-gradient-to-br from-primary via-primary to-secondary')

@section('contenido')
    <div class="flex min-h-[80vh] flex-col items-center justify-center gap-6">
        <div class="text-center text-white">
            <p class="text-4xl font-bold tracking-tight">TrekkMe</p>
            <p class="mt-1 text-sm text-white/80">Aviso de salida y búsqueda de excursionistas</p>
        </div>
        <div class="card w-full max-w-sm bg-base-100 shadow-2xl">
            <form class="card-body gap-4" method="POST" action="/login">
                <h1 class="card-title text-xl">Panel de administración</h1>
                @csrf
                <label class="flex flex-col gap-1">
                    <span class="text-sm font-semibold">Correo</span>
                    <input class="input w-full" type="email" name="email" value="{{ old('email') }}" required autofocus>
                </label>
                <label class="flex flex-col gap-1">
                    <span class="text-sm font-semibold">Contraseña</span>
                    <input class="input w-full" type="password" name="password" required>
                </label>
                <button class="btn btn-primary mt-2">Ingresar</button>
            </form>
        </div>
    </div>
@endsection
