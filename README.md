# TrekkMe

Sistema de aviso de salida y búsqueda de excursionistas. El repo tiene tres apps independientes que se comunican solo a través de la API de Laravel. Los datos viven en **Firebase (Firestore)**, no en una base de datos local.

| Carpeta | Qué es | Quién la usa | URL local |
|---|---|---|---|
| [`laravel/`](laravel) | API REST + panel web de administración (Laravel 13) | Las dos apps / el administrador | http://localhost:8000 |
| [`ionic/`](ionic) | App institucional (Ionic 9 + Angular 22) | Carabineros y rescatistas | http://localhost:8100 |
| [`trekkme_flutter/`](trekkme_flutter) | App móvil (Flutter) | Excursionistas | http://localhost:5555 (Chrome) |
| [`documents/`](documents) | Acta, modelo de datos, casos de uso y decisiones de negocio | Todo el equipo | — |

---

## 1. Requisitos

Instala esto antes de empezar y verifica que cada comando funcione en una terminal nueva:

| Herramienta | Versión | Verificar con | Descarga |
|---|---|---|---|
| Git | cualquiera reciente | `git --version` | https://git-scm.com |
| PHP | **8.3 o superior** | `php -v` | https://windows.php.net/download (o XAMPP / Laravel Herd) |
| Composer | 2.x | `composer --version` | https://getcomposer.org |
| Node.js | **22.22+ o 24.15+** (lo exige Angular 22) | `node -v` | https://nodejs.org (versión LTS) |
| Flutter | SDK con **Dart 3.13+** | `flutter --version` | https://docs.flutter.dev/get-started/install |
| Google Chrome | cualquiera | — | Flutter se ejecuta en Chrome |

**Extensiones de PHP** que deben estar activas en `php.ini`: `curl`, `openssl`, `mbstring`, `fileinfo`. Revisa con `php -m`. En Windows se activan quitando el `;` de las líneas `extension=curl`, `extension=openssl`, etc. en el `php.ini` que muestra `php --ini`.

Después de instalar Flutter, ejecuta `flutter doctor` y confirma que aparezca **Chrome** con un ✓ (lo demás —Android Studio, Xcode— no es necesario para la demo web).

---

## 2. Clonar el repositorio

```powershell
git clone <URL-del-repo> TrekkMe
```

```powershell
cd TrekkMe
```

---

## 3. Credenciales de Firebase (obligatorio)

La API de Laravel lee y escribe en Firestore con una **cuenta de servicio**. Ese archivo es secreto y **no está en el repo**; pídeselo al equipo (o, si tienes acceso al proyecto `trekkme-f8104`, descárgalo en *Firebase Console → Configuración del proyecto → Cuentas de servicio → Generar nueva clave privada*).

Guarda el `.json` **fuera de la carpeta del repo**, por ejemplo `C:\credenciales\trekkme-firebase.json`.

---

## 4. Instalar dependencias y configurar

### 4.1 Laravel (API)

```powershell
cd laravel
```

```powershell
composer install
```

```powershell
copy .env.example .env
```

```powershell
php artisan key:generate
```

Abre `laravel/.env` y completa las tres variables del final:

```env
FIREBASE_PROJECT_ID=trekkme-f8104
FIREBASE_API_KEY=AIzaSyBoP59AemTbYspygqD5OQZPg3QBmO-dIbM
FIREBASE_CREDENTIALS=C:/credenciales/trekkme-firebase.json
```

- `FIREBASE_API_KEY` es la clave web pública del proyecto (la misma de `ionic/src/environments/environment.ts`), no es un secreto.
- `FIREBASE_CREDENTIALS` es la ruta absoluta al JSON del paso 3. Usa `/` en la ruta, también en Windows.

Luego limpia la caché de configuración y vuelve a la raíz:

```powershell
php artisan config:clear
```

```powershell
cd ..
```

> No hace falta `php artisan migrate`: el proyecto no usa base de datos SQL, todo está en Firestore.

### 4.2 Ionic

```powershell
cd ionic
```

```powershell
npm install
```

```powershell
cd ..
```

### 4.3 Flutter

```powershell
cd trekkme_flutter
```

```powershell
flutter pub get
```

```powershell
cd ..
```

---

## 5. Datos de prueba y usuarios

Esto solo se hace **una vez** por proyecto de Firebase (si el equipo ya cargó los datos, puedes saltarlo; el comando nunca pisa lo que ya existe).

Desde `laravel/`:

```powershell
php artisan firestore:seed
```

Carga jurisdicciones, senderos, excursiones de ejemplo y estos usuarios, todos con contraseña **`password`**:

| Correo | Rol | App |
|---|---|---|
| `carabinero@trekkme.test` | Carabinero (jurisdicción Concepción) | Ionic |
| `carabinero2@trekkme.test` | Carabinero (jurisdicción 2) | Ionic |
| `andres.poblete@trekkme.test` | Rescatista | Ionic |
| `carolina.henriquez@trekkme.test` | Rescatista | Ionic |
| `tomas.riquelme@trekkme.test` | Rescatista | Ionic |

**Administrador del panel web** (necesario para crear excursionistas y otros usuarios):

```powershell
php artisan admin:crear
```

Te pedirá correo, nombre y contraseña. Con ese usuario entras a http://localhost:8000/login y en **Usuarios → Crear** creas un usuario con rol **Excursionista** para probar la app Flutter.

Si olvidas una contraseña:

```powershell
php artisan usuario:reset-password
```

---

## 6. Levantar el proyecto

### Opción A: todo de una vez (recomendado)

Desde la raíz del repo, en **Windows**:

```powershell
powershell -ExecutionPolicy Bypass -File .\iniciar-demo.ps1
```

En **macOS / Linux**:

```bash
./iniciar-demo.sh
```

El script revisa que estén los programas y que los puertos 8000, 8100 y 5555 estén libres, instala lo que falte, levanta la API, el programador de alertas e Ionic (en Windows cada uno en su propia ventana), abre Ionic en el navegador y finalmente ejecuta Flutter en Chrome. **Presiona `q` en la terminal de Flutter para apagar todo.**

### Opción B: cada servicio a mano

Abre **cuatro terminales**, una por servicio. Primero define en cada terminal de Laravel los orígenes permitidos por CORS:

**Terminal 1 – API Laravel**

```powershell
cd laravel
```

```powershell
$env:CORS_ALLOWED_ORIGINS = "http://localhost:8100,http://localhost:5555"
```

```powershell
php artisan serve --host=127.0.0.1 --port=8000 --no-reload
```

**Terminal 2 – Alertas automáticas** (cada minuto marca "en alerta" las excursiones que no volvieron a tiempo)

```powershell
cd laravel
```

```powershell
php artisan schedule:work
```

**Terminal 3 – Ionic**

```powershell
cd ionic
```

```powershell
npm start -- --port 8100
```

**Terminal 4 – Flutter**

```powershell
cd trekkme_flutter
```

```powershell
flutter run -d chrome --web-hostname=localhost --web-port=5555 --dart-define=API_URL=http://localhost:8000/api/v1
```

---

## 7. Dónde entrar

| Qué | URL | Usuario |
|---|---|---|
| App Ionic (carabinero / rescatista) | http://localhost:8100 | usuarios del paso 5 |
| App Flutter (excursionista) | http://localhost:5555 (se abre sola en Chrome) | el excursionista que creaste en el panel |
| Panel web de administración | http://localhost:8000/usuarios | el admin de `admin:crear` |
| Estado de la API | http://localhost:8000/up | — |

Para probar carabinero y rescatista al mismo tiempo, usa una ventana normal para uno y una de **incógnito** para el otro (la sesión se guarda por navegador).

Documentación de los endpoints: [`laravel/API.md`](laravel/API.md).

### Probar Flutter sin backend

Si solo quieres ver la app de excursionista sin levantar Laravel ni configurar Firebase:

```powershell
cd trekkme_flutter
```

```powershell
flutter run -d chrome --dart-define=USE_DEMO=true
```

---

## 8. Problemas frecuentes

| Síntoma | Solución |
|---|---|
| `falta 'php' en el PATH` (u otro programa) | Instálalo o agrega su carpeta a la variable de entorno PATH y abre una terminal nueva. |
| `el puerto 8000 está ocupado` | Cierra otro `php artisan serve`, Ionic o Flutter que tengas abierto. |
| `falta FIREBASE_... en laravel\.env` | Completa las tres variables del paso 4.1. |
| Error de credenciales / `file not found` al llamar la API | Revisa que `FIREBASE_CREDENTIALS` apunte a una ruta absoluta que exista y ejecuta `php artisan config:clear`. |
| `cURL error 60: SSL certificate problem` (Windows) | Descarga [cacert.pem](https://curl.se/ca/cacert.pem) y en `php.ini` define `curl.cainfo` y `openssl.cafile` con su ruta. |
| `The Angular CLI requires a minimum Node.js version` | Actualiza Node a 22.22+ o 24.15+. |
| Las apps muestran error de CORS o "no se pudo conectar" | La API no está arriba o se levantó sin `CORS_ALLOWED_ORIGINS`. Usa el script o los comandos de la opción B tal cual. |
| `No supported devices connected` en Flutter | Instala Chrome y revisa `flutter devices`. |
| El script `.ps1` no corre ("ejecución de scripts deshabilitada") | Ejecútalo exactamente con `powershell -ExecutionPolicy Bypass -File .\iniciar-demo.ps1`. |
| Las excursiones nunca pasan a "en alerta" | Falta el programador: `php artisan schedule:work` (el script ya lo levanta). |
