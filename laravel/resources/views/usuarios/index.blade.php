<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem; color: #222; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: .5rem .75rem; text-align: left; }
        th { background: #f4f4f4; }
        .vacio { color: #888; }
    </style>
</head>
<body>
    <h1>Usuarios registrados ({{ $usuarios->count() }})</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Email verificado</th>
                <th>Excursiones</th>
                <th>Bitácoras</th>
                <th>Registrado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->id }}</td>
                    <td>{{ $usuario->nombre }}</td>
                    <td>{{ $usuario->email }}</td>
                    <td>{{ $usuario->email_verificado_en?->format('d/m/Y H:i') ?? 'No' }}</td>
                    <td>{{ $usuario->excursiones_count }}</td>
                    <td>{{ $usuario->bitacoras_count }}</td>
                    <td>{{ $usuario->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="vacio">No hay usuarios registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
