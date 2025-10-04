#!/usr/bin/env bash
set -euo pipefail

# ===== CONFIG =====
VM_USER="${VM_USER:-super}"
VM_HOST="${VM_HOST:-10.3.1.135}"
SSH_PORT="${SSH_PORT:-22}"
CONTAINER_APP="${CONTAINER_APP:-controle-portaria-app}"
APP_ROOT_IN_CONTAINER="${APP_ROOT_IN_CONTAINER:-/var/www/html}"
TEST_USER_ID="${TEST_USER_ID:-36}"
APPLY=0 # use --apply para aplicar

# Liste aqui o que alterou no Replit (já está amplo e ok)
CANDIDATES=(
  "src/controllers/ConfigController.php"
  "src/controllers/AuthController.php"
  "src/services"
  "public/index.php"
  "public/.htaccess"
  "public/assets/js"
  "public/assets/css"
  "config/config.php"
  "config/database.php"
  "views/config/index.php"
  "views/layouts"
)

if [[ "${1:-}" == "--apply" ]]; then APPLY=1; fi

need(){ command -v "$1" >/dev/null 2>&1 || { echo "Falta '$1' no Replit: $1"; exit 1; }; }
need ssh; need scp; need tar; need awk; need sed; need find
if command -v sha256sum >/dev/null 2>&1; then SHA="sha256sum"; else SHA="shasum -a 256"; fi

echo "[1/8] Teste SSH $VM_USER@$VM_HOST:$SSH_PORT"
set +e
ssh -o BatchMode=yes -o ConnectTimeout=6 -p "$SSH_PORT" "$VM_USER@$VM_HOST" "echo ok" >/dev/null
RC=$?
set -e
if [ $RC -ne 0 ]; then
  echo "Não consegui conectar por SSH do Replit até $VM_HOST:$SSH_PORT."
  echo "Se sua rede não permite, use o Caminho B (download por URL)."
  exit 2
fi

STAMP="$(date +%Y%m%d-%H%M%S)"
WORK="/tmp/hotfix_portaria_$STAMP"
mkdir -p "$WORK/local" "$WORK/remote"
MAN_LOCAL="$WORK/local/MANIFEST.local.txt"
MAN_REMOTE="$WORK/remote/MANIFEST.remote.txt"

echo "[2/8] Coletando arquivos do Replit…"
LOCAL_LIST=()
for item in "${CANDIDATES[@]}"; do
  if [ -d "$item" ]; then
    while IFS= read -r -d '' f; do LOCAL_LIST+=("$f"); done < <(find "$item" -type f -print0)
  elif [ -f "$item" ]; then
    LOCAL_LIST+=("$item")
  fi
done
mapfile -t LOCAL_LIST < <(printf "%s\n" "${LOCAL_LIST[@]}" | sort -u)
[ ${#LOCAL_LIST[@]} -eq 0 ] && { echo "Nenhum arquivo alvo encontrado. Ajuste CANDIDATES."; exit 1; }
printf '  + %s\n' "${LOCAL_LIST[@]}"

echo "[3/8] Empacotando local…"
for f in "${LOCAL_LIST[@]}"; do
  mkdir -p "$WORK/local/$(dirname "$f")"
  cp -a "$f" "$WORK/local/$f"
done
( cd "$WORK/local" && tar -czf "$WORK/hotfix_local.tar.gz" . )
( cd "$WORK/local" && find . -type f -print0 | xargs -0 $SHA | sed 's|  \./|  |' ) > "$MAN_LOCAL" || true

echo "[4/8] Coletando remoto (do container)…"
REMOTE_TMP="/tmp/portaria_hotfix_$STAMP"
ssh -p "$SSH_PORT" "$VM_USER@$VM_HOST" "mkdir -p '$REMOTE_TMP'"
scp -P "$SSH_PORT" "$WORK/hotfix_local.tar.gz" "$VM_USER@$VM_HOST:$REMOTE_TMP/hotfix.tar.gz" >/dev/null
ssh -p "$SSH_PORT" "$VM_USER@$VM_HOST" "tar -tzf '$REMOTE_TMP/hotfix.tar.gz' > '$REMOTE_TMP/rel_paths.txt'"

ssh -p "$SSH_PORT" "$VM_USER@$VM_HOST" bash -lc "'
set -euo pipefail
mkdir -p \"$REMOTE_TMP/remote_tree\"
while read -r rel; do
  [ -z \"\$rel\" ] && continue
  [[ \"\$rel\" == */ ]] && continue
  SRC=\"$APP_ROOT_IN_CONTAINER/\$rel\"
  if docker exec -i $CONTAINER_APP bash -lc \"test -f '\$SRC'\" >/dev/null 2>&1; then
    mkdir -p \"\$REMOTE_TMP/remote_tree/\$(dirname \"\$rel\")\"
    docker cp $CONTAINER_APP:\"\$SRC\" \"\$REMOTE_TMP/remote_tree/\$rel\"
  fi
done < \"$REMOTE_TMP/rel_paths.txt\"
tar -czf \"$REMOTE_TMP/remote_current.tar.gz\" -C \"$REMOTE_TMP/remote_tree\" .
'"

scp -P "$SSH_PORT" "$VM_USER@$VM_HOST:$REMOTE_TMP/remote_current.tar.gz" "$WORK/remote_current.tar.gz" >/dev/null 2>&1 || true
mkdir -p "$WORK/remote"
[ -s "$WORK/remote_current.tar.gz" ] && tar -xzf "$WORK/remote_current.tar.gz" -C "$WORK/remote" >/dev/null 2>&1 || true
( cd "$WORK/remote" && find . -type f -print0 | xargs -0 $SHA | sed 's|  \./|  |' ) > "$MAN_REMOTE" || true

echo "[5/8] Diferenças LOCAL x REMOTO"
DIFF_FOUND=0
while IFS= read -r lf; do
  rel="${lf#*  }"
  if [ -f "$WORK/remote/$rel" ] && [ -f "$WORK/local/$rel" ]; then
    if ! diff -u "$WORK/remote/$rel" "$WORK/local/$rel" >/dev/null; then
      echo "### diff: $rel"
      diff -u "$WORK/remote/$rel" "$WORK/local/$rel" || true
      DIFF_FOUND=1
    fi
  elif [ -f "$WORK/local/$rel" ] && [ ! -f "$WORK/remote/$rel" ]; then
    echo "### novo (não existe no container): $rel"
    DIFF_FOUND=1
  fi
done < <(cut -d' ' -f2- "$MAN_LOCAL")
[ $DIFF_FOUND -eq 0 ] && echo "(sem diferenças nos arquivos coletados)"

if [ "$APPLY" -eq 1 ]; then
  echo "[6/8] Aplicando (backup + reload Apache)…"
  ssh -p "$SSH_PORT" "$VM_USER@$VM_HOST" bash -lc "'
set -euo pipefail
REMOTE_TMP=\"$REMOTE_TMP\"
CONTAINER_APP=\"$CONTAINER_APP\"
APP_ROOT=\"$APP_ROOT_IN_CONTAINER\"
mkdir -p \"\$REMOTE_TMP/apply\"
tar -xzf \"\$REMOTE_TMP/hotfix.tar.gz\" -C \"\$REMOTE_TMP/apply\"
BKP=\"\$APP_ROOT/.hotfix_backup/$(date +%Y%m%d-%H%M%S)\"
docker exec -i \"\$CONTAINER_APP\" bash -lc \"mkdir -p '\$BKP'\"
mapfile -t RELS < <(cd \"\$REMOTE_TMP/apply\" && find . -type f | sed \"s|^\./||\")
APPLIED=0
for rel in \"\${RELS[@]}\"; do
  SRC=\"\$REMOTE_TMP/apply/\$rel\"
  DST=\"\$APP_ROOT/\$rel\"
  docker exec -i \"\$CONTAINER_APP\" bash -lc \"if [ -f '\$DST' ]; then mkdir -p \\\$(dirname '\$BKP/\$rel'); cp -a '\$DST' '\$BKP/\$rel'; fi\"
  docker exec -i \"\$CONTAINER_APP\" bash -lc \"mkdir -p \\\$(dirname '\$DST')\"
  docker cp \"\$SRC\" \"\${CONTAINER_APP}:\$DST\"
  echo \" + \$rel\"
  APPLIED=\$((APPLIED+1))
done
echo \"Arquivos aplicados: \$APPLIED\"
docker exec -i \"\$CONTAINER_APP\" bash -lc \"apachectl -t\"
docker exec -i \"\$CONTAINER_APP\" bash -lc \"service apache2 reload\"
echo \"Backup em: \$BKP\"
'"

  echo "[7/8] Testes HTTP"
  ssh -p "$SSH_PORT" "$VM_USER@$VM_HOST" "bash -lc '
    set -e
    echo -n \"[POST] /config/users/$TEST_USER_ID/toggle-status -> \"; curl -s -o /dev/null -w \"%{http_code}\\n\" -X POST http://localhost:8080/config/users/$TEST_USER_ID/toggle-status || true
    echo -n \"[POST] /users/$TEST_USER_ID/toggle-status  -> \"; curl -s -o /dev/null -w \"%{http_code}\\n\" -X POST http://localhost:8080/users/$TEST_USER_ID/toggle-status || true
  '"
else
  echo ">> Só comparação. Para aplicar:  $0 --apply"
fi

echo "[8/8] OK"
