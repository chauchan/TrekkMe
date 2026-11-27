# Levanta TrekkMe completo para la demo (Windows PowerShell):
#   API Laravel      http://localhost:8000
#   Alertas          php artisan schedule:work
#   Ionic            http://localhost:8100
#   Flutter (Chrome) http://localhost:5555
#
# Uso (desde la raiz del repo):
#   powershell -ExecutionPolicy Bypass -File .\iniciar-demo.ps1
# La API, las alertas e Ionic se abren en ventanas aparte. Al presionar q en
# la ventana de Flutter se cierran todas.
# En macOS / Linux usar iniciar-demo.sh
#
# Archivo sin tildes a proposito: Windows PowerShell 5 las lee mal.

$ErrorActionPreference = 'Stop'

$Raiz = $PSScriptRoot
$PuertoApi = 8000
$PuertoIonic = 8100
$PuertoFlutter = 5555

function Fallar($mensaje) {
    Write-Host "ERROR: $mensaje" -ForegroundColor Red
    exit 1
}

function Revisar($paso) {
    if ($LASTEXITCODE -ne 0) { Fallar "fallo '$paso'." }
}

# --- Revisiones previas ---------------------------------------------------

foreach ($programa in 'php', 'composer', 'node', 'npm', 'flutter') {
    if (-not (Get-Command $programa -ErrorAction SilentlyContinue)) {
        Fallar "falta '$programa' en el PATH."
    }
}

foreach ($puerto in $PuertoApi, $PuertoIonic, $PuertoFlutter) {
    if (Get-NetTCPConnection -State Listen -LocalPort $puerto -ErrorAction SilentlyContinue) {
        Fallar "el puerto $puerto esta ocupado. Cierra lo que tengas corriendo (php artisan serve, ionic, flutter)."
    }
}

# --- Dependencias y configuracion (solo lo que falte) ---------------------

Set-Location "$Raiz\laravel"
if (-not (Test-Path vendor)) { composer install; Revisar 'composer install' }
if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
    php artisan key:generate
    Fallar "se creo laravel\.env. Completa FIREBASE_PROJECT_ID, FIREBASE_API_KEY y FIREBASE_CREDENTIALS y vuelve a ejecutar."
}
foreach ($variable in 'FIREBASE_PROJECT_ID', 'FIREBASE_API_KEY', 'FIREBASE_CREDENTIALS') {
    if (-not (Select-String -Path .env -Pattern "^$variable=.+" -Quiet)) {
        Fallar "falta $variable en laravel\.env."
    }
}
php artisan config:clear | Out-Null
Revisar 'php artisan config:clear'

if (-not (Test-Path "$Raiz\ionic\node_modules")) {
    Set-Location "$Raiz\ionic"
    npm install
    Revisar 'npm install'
}
Set-Location "$Raiz\trekkme_flutter"
flutter pub get | Out-Null
Revisar 'flutter pub get'

# --- Arranque -------------------------------------------------------------

# Origenes que la API acepta desde el navegador. Manda sobre lo que diga .env;
# --no-reload es necesario para que "artisan serve" respete la variable.
$env:CORS_ALLOWED_ORIGINS = "http://localhost:$PuertoIonic,http://localhost:$PuertoFlutter"
$env:NG_CLI_ANALYTICS = 'false'

$ventanas = @()

# Abre un servicio en su propia ventana; queda abierta si falla, para leer el error
function Lanzar($titulo, $carpeta, $comando) {
    Start-Process -FilePath 'cmd.exe' -ArgumentList "/k title $titulo && $comando" `
        -WorkingDirectory $carpeta -PassThru
}

function Esperar($nombre, $url, $segundos) {
    Write-Host "Esperando $nombre..."
    for ($i = 0; $i -lt $segundos; $i++) {
        try {
            Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 5 | Out-Null
            return
        } catch {
            Start-Sleep -Seconds 1
        }
    }
    throw "$nombre no respondio. Revisa su ventana."
}

try {
    $ventanas += Lanzar 'TrekkMe-API' "$Raiz\laravel" "php artisan serve --host=127.0.0.1 --port=$PuertoApi --no-reload"
    $ventanas += Lanzar 'TrekkMe-Alertas' "$Raiz\laravel" 'php artisan schedule:work'
    $ventanas += Lanzar 'TrekkMe-Ionic' "$Raiz\ionic" "npm start -- --port $PuertoIonic"

    Esperar 'la API' "http://localhost:$PuertoApi/up" 30
    Esperar 'Ionic' "http://localhost:$PuertoIonic" 180

    Write-Host ''
    Write-Host 'TrekkMe arriba.' -ForegroundColor Green
    Write-Host "  Ionic (carabinero y rescatista): http://localhost:$PuertoIonic"
    Write-Host '     usa una ventana normal para uno y una de incognito para el otro'
    Write-Host "  Panel web (crear usuarios):      http://localhost:$PuertoApi/usuarios"
    Write-Host ''
    Write-Host 'Abriendo Flutter en Chrome. Presiona q para apagar todo.'
    Write-Host ''

    Start-Process "http://localhost:$PuertoIonic"

    Set-Location "$Raiz\trekkme_flutter"
    flutter run -d chrome --web-hostname=localhost "--web-port=$PuertoFlutter" "--dart-define=API_URL=http://localhost:$PuertoApi/api/v1"
}
finally {
    Write-Host 'Apagando servicios...'
    foreach ($ventana in $ventanas) {
        # /T cierra tambien los procesos hijos (php, node)
        if (-not $ventana.HasExited) { taskkill /PID $ventana.Id /T /F | Out-Null }
    }
    Set-Location $Raiz
}
