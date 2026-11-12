# TrekkMe — Cómo levantar la demo de la feria

Guía rápida para dejar corriendo las tres cosas que se muestran: el **panel de Laravel** (admin), la **app Ionic** (carabinero y rescatista) y el **simulador** de excursionistas.

Se necesitan **tres terminales** abiertas a la vez.

---

## 0. Antes de empezar (solo la primera vez)

Requisitos: PHP 8.2+ (Herd), Composer, Node.js y npm.

```bash
cd laravel
composer install
npm install
npm run build
```

Revisar que `laravel/.env` tenga `FIREBASE_PROJECT_ID`, `FIREBASE_API_KEY` y `FIREBASE_CREDENTIALS` (la ruta al JSON de la cuenta de servicio).

```bash
cd ionic
npm install
```

Cargar los datos base (jurisdicciones, senderos, usuarios y casos de ejemplo). **No pisa nada de lo que ya existe**, así que se puede correr sin miedo:

```bash
cd laravel
php artisan firestore:seed
```

---

## 1. Terminal 1 — Backend y panel Laravel

```bash
cd laravel
php artisan serve
```

- API: `http://localhost:8000/api/v1`
- Panel admin: `http://localhost:8000/login`

## 2. Terminal 2 — App Ionic (carabinero y rescatista)

```bash
cd ionic
npm start -- --port 8100
```

Abrir `http://localhost:8100`.

> **Ojo con el puerto:** usar el **8100**. Laravel solo acepta peticiones (CORS) desde `localhost:8100`. Con el puerto por defecto (4200) la app carga, pero fallan acciones como escalar o cerrar.

Para mostrar los dos roles a la vez, abrir el carabinero en una ventana normal y el rescatista en una ventana de incógnito (o en otro navegador), para que no compartan la sesión.

## 3. Terminal 3 — Simulador

```bash
cd laravel
php artisan simulador:correr
```

Crea excursiones nuevas, las pasa a "en curso", envía ubicación y batería, y algunas terminan **en alerta**. Se detiene con `Ctrl+C`.

Valores por defecto: tick cada 20 s, excursiones de 3 a 5 minutos, 30 % se pierden, senderos de `jur-001`.

---

## Usuarios de prueba

| Rol | Dónde se usa | Correo | Contraseña | Jurisdicción |
|---|---|---|---|---|
| Admin | Panel Laravel | `sysadmin@trekkme.cl` | la que le pusieron al crearlo (ver abajo) | — |
| Carabinero | Ionic | `carabinero@trekkme.test` | `password` | jur-001 |
| Rescatista | Ionic | `andres.poblete@trekkme.test` | `password` | jur-001 |
| Rescatista (2º) | Ionic | `tomas.riquelme@trekkme.test` | `password` | jur-001 |

Para la demo hay que usar el carabinero y el rescatista de **jur-001**. Las excursiones del simulador son de esa jurisdicción; con usuarios de otra jurisdicción no se ven.

Al escalar un caso, el carabinero debe asignárselo al rescatista que tiene la sesión abierta. Si no, el caso no aparece en "Casos asignados".

**Si una contraseña no funciona** (por ejemplo, la del admin), se puede dejar en una conocida con:

```bash
cd laravel
php artisan usuario:reset-password sysadmin@trekkme.cl --password=password
```

Sirve igual para cualquier otro correo de la tabla.

---

## Guion sugerido para mostrar

1. **Carabinero:** van apareciendo excursiones en la bandeja; algunas se ponen en rojo (**en alerta**).
2. **Carabinero:** abre una en alerta y la **escala**, asignándola al rescatista.
3. **Rescatista:** el caso aparece en "Casos asignados". Abrir la ficha y la vista de terreno: la ubicación y la batería se siguen actualizando hasta que "se apaga el teléfono".
4. **Rescatista:** agrega un registro de bitácora y **pide el cierre**.
5. **Carabinero:** **confirma el cierre**; el caso pasa al histórico.
6. **Panel Laravel:** mostrar usuarios, excursiones y bitácoras (el panel no es en vivo: hay que recargar la página).

---

## Entre una ronda y otra

Detener el simulador (`Ctrl+C`) y borrar lo simulado:

```bash
cd laravel
php artisan simulador:limpiar
```

Solo borra lo del simulador (IDs `sim-…` y sus bitácoras). Los casos de ejemplo (`exc-…`) y los usuarios no se tocan. Después se vuelve a correr `php artisan simulador:correr`.

---

## Opciones del simulador

| Opción | Para qué | Por defecto |
|---|---|---|
| `--intervalo=20` | Segundos entre ticks | 20 |
| `--duracion=3-5` | Minutos que dura cada excursión (mín-máx) | 3-5 |
| `--perdidas=30` | Porcentaje que se pierde | 30 |
| `--max-activas=4` | Máximo de excursiones planificadas o en curso a la vez | 4 |
| `--jurisdiccion=jur-001` | Jurisdicción de los senderos | jur-001 |
| `--semilla=7` | Repite siempre la misma secuencia | — |
| `--una-vez` | Hace un solo tick y termina | — |

Prueba rápida, todo acelerado (**no dejarlo así en la feria**, gasta más cuota de Firestore):

```bash
php artisan simulador:correr --intervalo=5 --duracion=1
```

---

## Si algo falla

| Problema | Qué revisar |
|---|---|
| No aparecen excursiones en la bandeja | Que el simulador esté corriendo y que el carabinero sea de `jur-001` |
| El caso escalado no le aparece al rescatista | Que se haya asignado al mismo rescatista que tiene la sesión abierta |
| Escalar o cerrar da error en Ionic | Que Laravel esté corriendo y que Ionic esté en el puerto 8100 |
| El simulador dice "No hay senderos" | Correr `php artisan firestore:seed` |
| El simulador muestra "Error en el tick" | Problema de red puntual: sigue solo en el próximo tick |
