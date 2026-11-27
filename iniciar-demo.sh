#!/usr/bin/env bash
# Levanta TrekkMe completo para la demo (macOS / Linux):
#   API Laravel      http://localhost:8000
#   Alertas          php artisan schedule:work
#   Ionic            http://localhost:8100
#   Flutter (Chrome) http://localhost:5555
#
# Uso:  ./iniciar-demo.sh        (q en esta terminal, o Ctrl+C, apaga todo)
# En Windows usar iniciar-demo.ps1

set -euo pipefail

RAIZ="$(cd "$(dirname "$0")" && pwd)"
LOGS="$RAIZ/.demo-logs"
PUERTO_API=8000
PUERTO_IONIC=8100
PUERTO_FLUTTER=5555
PIDS=()

error() { echo "ERROR: $*" >&2; exit 1; }

# --- Revisiones previas ---------------------------------------------------

for programa in php composer node npm flutter curl lsof; do
  command -v "$programa" >/dev/null 2>&1 || error "falta '$programa' en el PATH."
done

for puerto in $PUERTO_API $PUERTO_IONIC $PUERTO_FLUTTER; do
  if lsof -nP -iTCP:"$puerto" -sTCP:LISTEN >/dev/null 2>&1; then
    error "el puerto $puerto está ocupado. Cierra lo que tengas corriendo (php artisan serve, ionic, flutter)."
  fi
done

# --- Dependencias y configuración (solo lo que falte) ---------------------

cd "$RAIZ/laravel"
[ -d vendor ] || composer install
if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate
  error "se creó laravel/.env. Completa FIREBASE_PROJECT_ID, FIREBASE_API_KEY y FIREBASE_CREDENTIALS y vuelve a ejecutar."
fi
for variable in FIREBASE_PROJECT_ID FIREBASE_API_KEY FIREBASE_CREDENTIALS; do
  grep -Eq "^$variable=.+" .env || error "falta $variable en laravel/.env."
done
php artisan config:clear >/dev/null

[ -d "$RAIZ/ionic/node_modules" ] || (cd "$RAIZ/ionic" && npm install)
(cd "$RAIZ/trekkme_flutter" && flutter pub get >/dev/null)

# --- Arranque -------------------------------------------------------------

mkdir -p "$LOGS"

apagar() {
  trap - EXIT INT TERM
  echo
  echo "Apagando servicios..."
  for pid in ${PIDS[@]+"${PIDS[@]}"}; do
    kill -TERM -- "-$pid" 2>/dev/null || true   # el grupo completo, con sus hijos
  done
}
trap apagar EXIT
trap 'exit 130' INT TERM

# Cada servicio en segundo plano queda en su propio grupo de procesos
set -m

# lanzar <nombre> <carpeta> <comando...>
lanzar() {
  local nombre="$1" carpeta="$2"
  shift 2
  (cd "$carpeta" && exec "$@") >"$LOGS/$nombre.log" 2>&1 </dev/null &
  PIDS+=("$!")
}

# esperar <nombre> <url> <segundos>
esperar() {
  local nombre="$1" url="$2" intentos="$3"
  echo "Esperando $nombre..."
  until curl -fs -o /dev/null "$url"; do
    intentos=$((intentos - 1))
    if [ "$intentos" -le 0 ]; then
      tail -n 30 "$LOGS/$nombre.log" >&2
      error "$nombre no respondió. Log completo en .demo-logs/$nombre.log"
    fi
    sleep 1
  done
}

# Orígenes que la API acepta desde el navegador. Manda sobre lo que diga .env;
# --no-reload es necesario para que "artisan serve" respete la variable.
export CORS_ALLOWED_ORIGINS="http://localhost:$PUERTO_IONIC,http://localhost:$PUERTO_FLUTTER"
export NG_CLI_ANALYTICS=false

lanzar api     "$RAIZ/laravel" php artisan serve --host=127.0.0.1 --port=$PUERTO_API --no-reload
lanzar alertas "$RAIZ/laravel" php artisan schedule:work
lanzar ionic   "$RAIZ/ionic"   npm start -- --port $PUERTO_IONIC

esperar api   "http://localhost:$PUERTO_API/up" 30
esperar ionic "http://localhost:$PUERTO_IONIC" 180

cat <<EOF

TrekkMe arriba.
  Ionic (carabinero y rescatista): http://localhost:$PUERTO_IONIC
     usa una ventana normal para uno y una de incógnito para el otro
  Panel web (crear usuarios):      http://localhost:$PUERTO_API/usuarios
  Logs:                            .demo-logs/

Abriendo Flutter en Chrome. Presiona q (o Ctrl+C) para apagar todo.

EOF

# Abre Ionic en el navegador por defecto
if command -v open >/dev/null 2>&1; then
  open "http://localhost:$PUERTO_IONIC"
elif command -v xdg-open >/dev/null 2>&1; then
  xdg-open "http://localhost:$PUERTO_IONIC" >/dev/null 2>&1 || true
fi

cd "$RAIZ/trekkme_flutter"
flutter run -d chrome \
  --web-hostname=localhost \
  --web-port=$PUERTO_FLUTTER \
  --dart-define=API_URL=http://localhost:$PUERTO_API/api/v1
