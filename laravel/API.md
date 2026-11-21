# API TrekkMe (Laravel)

Contrato para la app Ionic (carabinero y rescatista).

**Base URL:** `http://<host>:8000/api/v1`
En el emulador Android el host es `10.0.2.2`. En iOS Simulator, `localhost`.

## Principio: leer de Firestore, escribir por la API

- La app **lee en tiempo real directo de Firestore** (`onSnapshot`): `excursiones`, `avisos`, `bitacora`, etc.
- La app **escribe solo por esta API**: cambios de estado y bitácora. La API valida permisos, valida los datos y detecta conflictos.
- Firestore es la única base de datos. Laravel escribe con cuenta de servicio.

## Autenticación

1. `POST /login` con correo y contraseña. Devuelve un `firebase_token`.
2. La app lo canjea: `signInWithCustomToken(auth, firebase_token)`.
3. En cada petición a la API, enviar el **ID token de Firebase** (no el `firebase_token`):

```ts
const idToken = await auth.currentUser.getIdToken();
// Authorization: Bearer <idToken>
```

`getIdToken()` renueva el token solo cuando vence. El interceptor debe llamarlo en cada petición.
El `uid` de Firebase es el id del usuario (`resc-001`, `carab-002`). Los claims `rol` y `jurisdiccionId` vienen en el token y también se pueden leer en las reglas de Firestore.

Cabeceras: `Accept: application/json` y `Content-Type: application/json`.

## Formato de respuesta

- Éxito: `{ "data": ... }`. Los 204 no traen cuerpo.
- Error: `{ "message": "..." }`. En 422 se agrega `errors: { campo: ["mensaje"] }`. Los mensajes vienen en español y se pueden mostrar tal cual.

| Código | Cuándo | Qué hace la app |
|---|---|---|
| 401 | Sin token, token inválido o vencido | Cerrar sesión y volver al login |
| 403 | Rol, jurisdicción o caso ajeno | Mostrar `message` |
| 404 | El aviso, caso o registro ya no existe | Mostrar `message` y volver a la lista |
| 409 | Estado o versión desactualizados | Mostrar `message` y recargar el dato |
| 422 | Datos inválidos o login incorrecto | Mostrar `errors` |
| 429 | Demasiados intentos de login | Esperar |

**Importante:** un login con credenciales malas responde **422**, no 401, para que el interceptor no lo confunda con una sesión expirada.

## Endpoints

### `POST /login`
Pública. Máximo 5 intentos por minuto por correo e IP.

```json
{ "email": "carabinero@trekkme.test", "password": "..." }
```
200:
```json
{ "data": {
  "firebase_token": "eyJ...",
  "perfil": "carabinero",
  "usuario_id": "carab-001",
  "nombre": "Carabinero de Prueba",
  "jurisdiccion_id": "jur-001"
} }
```
- `perfil`: `carabinero`, `rescatista` o `excursionista` (app Flutter). Otros roles reciben **403**.
- 422 si el correo o la contraseña son incorrectos.

### `GET /yo`
Devuelve quién es el usuario según el token: `{ "data": { "id", "rol", "jurisdiccionId" } }`. Sirve para probar la sesión.

### `POST /logout`
204. No hay sesión en el servidor. La app cierra su sesión de Firebase (`signOut`).

### `GET /senderos`
Roles: carabinero, rescatista, excursionista. Para llenar selectores.
```json
{ "data": [ { "id": "sen-001", "nombre": "...", "jurisdiccion_id": "jur-001" } ] }
```

## Excursionista (`/excursionista/...`)

App Flutter. Solo ve y crea **sus propias** excursiones (las que tienen su id en `excursionistaId`). No lee Firestore: todo pasa por esta API.

### `GET /excursionista/excursiones`
Mis excursiones, las más recientes primero. Sin datos médicos.
```json
{ "data": [ {
  "id": "exc-010", "sendero_id": "sen-001", "sendero": "PN Nahuelbuta - Piedra del Águila",
  "estado": "planificada",
  "salida_at": "2026-11-21T08:00:00-03:00", "retorno_at": "2026-11-21T16:00:00-03:00",
  "contacto_nombre": "Paula Díaz", "contacto_telefono": "+56 9 5555 0108",
  "detalle": null, "lleva_carpa": false, "color_carpa": null, "lleva_linterna": true,
  "acompanantes": [ { "nombre": "Tomás Garrido", "rut": "20.000.015-5" } ],
  "ultima_lat": null, "ultima_lng": null, "ultima_ubicacion_at": null, "bateria": null
} ] }
```

### `POST /excursionista/excursiones`
Avisa una salida. Queda `planificada`, sin rescatista. El nombre sale de la cuenta del token.

```json
{
  "sendero_id": "sen-001",
  "salida_at": "2026-11-21T08:00:00-03:00",
  "retorno_at": "2026-11-21T16:00:00-03:00",
  "contacto_nombre": "Paula Díaz", "contacto_telefono": "+56 9 5555 0108",
  "rut": "20.000.004-4", "telefono": "+56 9 5555 0107", "edad": 23,
  "experiencia": "Principiante", "condiciones_medicas": "Ninguna",
  "detalle": "Polerón amarillo.", "lleva_carpa": false, "color_carpa": null, "lleva_linterna": true,
  "acompanantes": [ { "nombre": "Tomás Garrido", "rut": "20.000.015-5", "edad": 23, "condiciones_medicas": "Ninguna" } ]
}
```
- Obligatorios: `sendero_id`, `salida_at`, `retorno_at` (posterior a la salida), `contacto_nombre`, `contacto_telefono`, `rut`, `telefono`, `edad`, `experiencia` (`Principiante`, `Intermedio` o `Avanzado`).
- Los datos personales (`rut`, `telefono`, `edad`, `experiencia`, `condiciones_medicas`) los envía la app: todavía no viven en el perfil del usuario.
- Los acompañantes son sin cuenta (solo datos mínimos).
- 201: `{ "data": { ...la excursión, igual que en el listado } }`
- 422 si falta un dato o el `sendero_id` no existe.

### `POST /excursionista/excursiones/{id}/{accion}`
Cambia el estado de una excursión **propia**. `accion`:

| accion | Estado actual → nuevo |
|---|---|
| `iniciar` | `planificada` → `en_curso` |
| `regresar` | `en_curso` → `cerrada` ("Ya volví") |

```json
{ "estado_esperado": "planificada" }
```
200: `{ "data": { "id": "exc-010", "estado": "en_curso" } }`

- `estado_esperado` es el estado que la app tiene en pantalla. Si ya cambió (por ejemplo, pasó a `en_alerta`), responde **409** "Tu excursión cambió de estado. Actualiza e intenta de nuevo."
- **403** si la excursión no es suya. **409** si la acción no corresponde al estado actual.
- `regresar` solo vale desde `en_curso`. Si la excursión ya está en alerta, la cierra el carabinero.

### `POST /excursionista/excursiones/{id}/ubicacion`
Guarda la última ubicación y la batería del teléfono. Se puede enviar mientras la excursión esté `en_curso`, `en_alerta`, `en_operativo` o `cierre_pendiente`.

```json
{ "lat": -37.8235, "lng": -73.0312, "bateria": 78 }
```
- `lat` entre -90 y 90, `lng` entre -180 y 180, `bateria` entero de 0 a 100 (opcional).
- 200: `{ "data": { "id", "ultima_lat", "ultima_lng", "ultima_ubicacion_at", "bateria" } }`
- **409** si la excursión no está en uno de esos estados (planificada o cerrada). **403** si no es suya.

## Paso automático a alerta

Ningún cliente pasa una excursión a `en_alerta`. Lo hace el comando programado `php artisan excursiones:marcar-alertas`: cada minuto pasa a `en_alerta` las excursiones `en_curso` cuya hora de retorno ya venció (y así aparecen en la bandeja del carabinero). Requiere el programador activo (`php artisan schedule:work`) o ejecutarlo a mano.

## Carabinero (`/carabinero/...`)

Solo puede actuar sobre avisos de **su jurisdicción**. Si no, 403.

### `GET /carabinero/rescatistas`
Rescatistas de su jurisdicción, para el selector al escalar.
```json
{ "data": [ { "id": "resc-001", "nombre": "Andrés Poblete" } ] }
```

### `POST /carabinero/excursiones/{id}/{accion}`
Cambia el estado de una excursión. `accion`:

| accion | Estado actual → nuevo | Body extra |
|---|---|---|
| `escalar` | `en_alerta` → `en_operativo` | `rescatista_id` (obligatorio) |
| `cerrar` | `en_alerta` → `cerrada` | |
| `confirmar-cierre` | `cierre_pendiente` → `cerrada` | |
| `rechazar-cierre` | `cierre_pendiente` → `en_operativo` | |

Body:
```json
{ "estado_esperado": "en_alerta", "rescatista_id": "resc-001" }
```
200: `{ "data": { "id": "exc-004", "estado": "en_operativo" } }`

- `estado_esperado` es el estado que la app tiene en pantalla. Si en Firestore ya cambió (otro carabinero se adelantó), responde **409** "Otra persona ya gestionó este aviso. Vuelve a abrirlo."
- 422 si `rescatista_id` no es un rescatista de su jurisdicción.

## Rescatista (`/rescatista/...`)

Solo puede actuar sobre casos que tiene **asignados** (`rescatistaId` de la excursión). Si no, 403 "No tienes acceso a este caso."

### `POST /rescatista/casos/{id}/solicitar-cierre`
`en_operativo` → `cierre_pendiente`.
```json
{ "estado_esperado": "en_operativo" }
```
200: `{ "data": { "id": "exc-004", "estado": "cierre_pendiente" } }`

### `POST /rescatista/casos/{casoId}/bitacora`
Crea un registro. El rescatista sale del token, no se envía.
```json
{
  "sendero_id": "sen-001",
  "hora_inicio": "2026-11-05T14:30:00-03:00",
  "hora_fin": "2026-11-05T15:10:00-03:00",
  "texto": "Se ubicó al grupo en el sector alto."
}
```
- `sendero_id` opcional. Sin él, la ruta queda como "Fuera de sendero".
- `hora_fin` opcional y posterior a `hora_inicio`.
- 201: `{ "data": { "id": "<20 caracteres>", "version": 1 } }`

### `PUT /rescatista/bitacora/{id}`
Edita un registro. Mismos campos que crear, más `version`: la que la app leyó de Firestore.
```json
{ "version": 1, "sendero_id": null, "hora_inicio": "...", "hora_fin": null, "texto": "..." }
```
200: `{ "data": { "id": "...", "version": 2 } }`
409 "Alguien modificó este registro. Vuelve a abrirlo e intenta de nuevo." si la `version` no coincide con la guardada.

### `DELETE /rescatista/bitacora/{id}`
204 sin cuerpo.

## Estados de una excursión

`planificada`, `en_curso`, `en_alerta`, `en_operativo`, `cierre_pendiente`, `cerrada`.
Siempre en minúscula y con guion bajo, tanto en la API como en Firestore.

```
en_alerta ──escalar──▶ en_operativo ──solicitar-cierre──▶ cierre_pendiente
    │                       ▲                                 │      │
    └──cerrar──▶ cerrada    └────────rechazar-cierre──────────┘      │
                     ▲                                               │
                     └────────────────confirmar-cierre───────────────┘
```

## Datos en Firestore que lee la app

- `avisos`: la excursión **sin datos médicos**, con `jurisdiccionId` y `senderoId`. La usa el carabinero.
- `excursiones`: ficha completa con datos médicos. La usa el rescatista asignado.
- `bitacora`: registros con `excursionId`, `rescatistaId`, `senderoId`, `ruta`, `horaInicio`, `horaFin`, `texto`, `version`.

## Probar rápido

```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"carabinero@trekkme.test","password":"..."}'
```
