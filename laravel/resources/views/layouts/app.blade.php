<!DOCTYPE html>
<html lang="es" data-theme="trekkme">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · TrekkMe</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen @yield('body', 'bg-base-200')">
    @if (session('admin'))
    <nav class="flex items-center gap-2 bg-primary px-6 py-3 text-white">
        <a href="/usuarios" class="mr-6 text-lg font-bold">TrekkMe</a>
        <div class="flex flex-1 gap-1">
            @foreach (['usuarios' => 'Usuarios', 'excursiones' => 'Excursiones', 'bitacoras' => 'Bitácoras'] as $ruta => $nombre)
                <a href="/{{ $ruta }}"
                   class="rounded-lg px-3 py-1.5 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70 {{ request()->is($ruta, "$ruta/*") ? 'bg-white/25 text-white' : 'text-white/80 hover:bg-white/15 hover:text-white' }}">{{ $nombre }}</a>
            @endforeach
        </div>
        <form method="POST" action="/logout">
            @csrf
            <button class="rounded-lg border border-white/60 px-3 py-1.5 text-sm font-semibold text-white transition-colors hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70">Salir ({{ session('admin.nombre') }})</button>
        </form>
    </nav>
    @endif
    <main class="p-6 max-w-7xl mx-auto">
        @if (session('ok'))
            <div class="alert alert-success mb-4"><span>{{ session('ok') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error mb-4">
                <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('contenido')
    </main>
</body>
</html>
