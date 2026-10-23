# TrekkMe — Backend Laravel

API REST que consumen la app institucional (`../ionic`) y la app del excursionista (`../flutter`).

## Estructura

Estructura estándar de Laravel:

```
app/
├── Enums/                  EstadoExcursion (máquina de estados)
├── Exceptions/             TransicionDeEstadoInvalida (responde 409)
├── Http/
│   ├── Controllers/Api/    Controladores de la API
│   └── Resources/          Formato JSON de cada respuesta
├── Models/                 Modelos Eloquent (User, Jurisdiccion, Area, Sendero)
└── Providers/
database/
├── migrations/
└── seeders/                TerritorioSeeder: mismos senderos que usa Ionic
routes/api.php              Rutas de la API
tests/                      Unit (enum de estados) y Feature (endpoints)
```

Para agregar un módulo nuevo (por ejemplo, la bitácora): migración, modelo, controlador en `Http/Controllers/Api`, FormRequest para validar, Resource para la respuesta, ruta en `routes/api.php` y su test de feature.

## Endpoints disponibles

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/areas` | Áreas activas del catálogo |
| `GET` | `/api/areas/{id}/senderos` | Senderos activos de un área (404 si no existe) |

## Máquina de estados

| Desde | Hacia | Motivo |
|---|---|---|
| `programada` | `en_curso`, `cancelada` | Salida confirmada / se cancela |
| `en_curso` | `finalizada` | Aviso de llegada |
| `en_curso` | `vencida` | Pasó la hora comprometida de retorno |
| `en_curso` | `en_alerta` | SOS |
| `vencida` | `en_curso` | Prórroga aceptada |
| `vencida` | `en_alerta` | El escalamiento llega a la unidad |
| `en_alerta` | `finalizada` | Falsa alarma (cierra el carabinero) |
| `en_alerta` | `en_operativo` | El carabinero escala y asigna rescatista |
| `en_operativo` | `cierre_pendiente` | El rescatista pide el cierre |
| `cierre_pendiente` | `finalizada` | El carabinero confirma el cierre |
| `cierre_pendiente` | `en_operativo` | El carabinero rechaza el cierre |

Toma la máquina del modelo de datos (`documents/TrekkMe_modelo_datos.png`) y le agrega `cierre_pendiente` según la decisión 3.1 de `documents/decisiones-de-negocio.md`.

## Desarrollo

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # usa SQLite (database/database.sqlite) por defecto
php artisan serve
php artisan test
```

Para usar MySQL de XAMPP, cambia en `.env` `DB_CONNECTION=mysql` y descomenta las variables `DB_*`.

## Pendiente (ver `documents/decisiones-de-negocio.md`, sección 7)

- Usuarios con rol, RUT y fecha de nacimiento + login con Sanctum
- Excursiones, participantes e invitaciones
- Operativos y bitácora con control de versión (409)
- Transiciones de estado vía endpoints (escalar, solicitar/confirmar/rechazar cierre)
- Rastro GPS con batería
- Columnas geográficas al pasar a una BD con soporte espacial
