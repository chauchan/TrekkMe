<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · TrekkMe</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; color: #222; }
        nav { background: #1d3557; padding: .75rem 2rem; display: flex; gap: 1.5rem; }
        nav a { color: #fff; text-decoration: none; font-weight: 600; }
        main { padding: 1.5rem 2rem; }
        table { border-collapse: collapse; width: 100%; font-size: .9rem; }
        th, td { border: 1px solid #ddd; padding: .4rem .6rem; text-align: left; vertical-align: top; }
        th { background: #f4f4f4; }
        .vacio { color: #888; }
        .barra { display: flex; justify-content: space-between; align-items: center; }
        .btn { background: #1d3557; color: #fff; padding: .5rem 1rem; border: 0; border-radius: 4px; text-decoration: none; cursor: pointer; font-size: .9rem; }
        .ok { background: #e6f4ea; border: 1px solid #9fd4ae; padding: .6rem 1rem; margin-bottom: 1rem; }
        .errores { background: #fdecea; border: 1px solid #f1a7a0; padding: .6rem 1rem; margin-bottom: 1rem; }
        form.alta { max-width: 640px; display: grid; gap: .8rem; }
        form.alta label { display: grid; gap: .2rem; font-weight: 600; font-size: .9rem; }
        form.alta input, form.alta select, form.alta textarea { padding: .45rem; font: inherit; font-weight: 400; }
        form.alta label.check { display: flex; align-items: center; gap: .5rem; }
        fieldset { border: 1px solid #ddd; padding: .8rem; display: grid; gap: .6rem; }
        .fila-acomp { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; }
    </style>
</head>
<body>
    @if (session('admin'))
    <nav>
        <a href="/usuarios">Usuarios</a>
        <a href="/excursiones">Excursiones</a>
        <a href="/bitacoras">Bitácoras</a>
        @if (session('admin'))
            <form method="POST" action="/logout" style="margin-left:auto">
                @csrf
                <button class="btn" style="background:transparent;border:1px solid #fff">Salir ({{ session('admin.nombre') }})</button>
            </form>
        @endif
    </nav>
    @endif
    <main>
        @if (session('ok'))
            <div class="ok">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="errores">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('contenido')
    </main>
</body>
</html>
