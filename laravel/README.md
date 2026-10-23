# TrekkMe — Backend Laravel

API REST que consumen la app institucional (`../ionic`) y la app del excursionista (`../flutter`).

## Arquitectura

Clean architecture en cuatro capas. Las dependencias apuntan solo hacia adentro: `Http → Application → Domain ← Infrastructure`.

```
app/
├── Domain/                 Reglas de negocio puras. No usa nada de Laravel.
│   ├── Shared/Exceptions/  Excepciones base (RecursoNoEncontrado → 404, ConflictoDeDominio → 409)
│   ├── Excursion/          Máquina de estados de la excursión (EstadoExcursion)
│   └── Territorio/         Entidades Area y Sendero + interfaz TerritorioRepository
├── Application/            Casos de uso. Orquestan el dominio a través de las interfaces.
│   └── Territorio/         ListarAreas, ListarSenderosDeArea
├── Infrastructure/         Detalles técnicos que implementan las interfaces del dominio.
│   └── Persistence/Eloquent/
│       ├── Models/         Modelos Eloquent (sufijo Model para no confundirlos con entidades)
│       └── Repositories/   EloquentTerritorioRepository
├── Http/                   Presentación: controladores delgados y Resources JSON.
└── Providers/
    └── DomainServiceProvider.php   Une cada interfaz con su implementación
```

Para agregar un módulo nuevo (por ejemplo, la bitácora):

1. **Domain**: entidad, reglas y la interfaz del repositorio.
2. **Application**: un caso de uso por acción (`CrearRegistroBitacora`, `EditarRegistroBitacora`, ...).
3. **Infrastructure**: modelo Eloquent y repositorio que implementa la interfaz.
4. Registrar el binding en `DomainServiceProvider`.
5. **Http**: controlador, FormRequest, Resource y ruta en `routes/api.php`.
6. Tests: unitarios para el dominio, de feature para el endpoint.

`app/Models/User.php` se dejó donde Laravel lo espera porque lo usan la autenticación y Sanctum.

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
