# TrekkMe

Sistema de aviso de salida y búsqueda de excursionistas. El repo tiene las tres partes del proyecto, cada una en su carpeta e independiente de las demás:

| Carpeta | Qué es | Quién la usa | Estado |
|---|---|---|---|
| [`ionic/`](ionic) | App institucional (Ionic + Angular) | Carabineros y rescatistas | Parte 1, terminada |
| [`laravel/`](laravel) | Backend API REST (Laravel 12 + Sanctum) | Las dos apps | Parte 2, en desarrollo |
| [`flutter/`](flutter) | App móvil (Flutter) | Excursionistas | Parte 3, pendiente |
| [`documents/`](documents) | Acta, modelo de datos, casos de uso y decisiones de negocio | Todo el equipo | — |

Las apps no comparten código: se comunican solo a través de la API de `laravel/`.

## Cómo levantar cada parte

### Ionic

```bash
cd ionic
npm install
npm start
```

### Laravel

Requiere PHP 8.2+ y Composer.

```bash
cd laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

La API queda en `http://localhost:8000/api`. Detalles de la arquitectura en [`laravel/README.md`](laravel/README.md).

### Flutter

Pendiente (parte 3).
