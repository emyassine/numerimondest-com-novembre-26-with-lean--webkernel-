#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
CONFIG="${WEB_SYNC_CONFIG:-$ROOT/web-sync.config.json}"

usage() {
    printf 'Usage: %s\n' "${0##*/}"
    printf '  Sync source contents, then rebuild production runtime artifacts.\n'
}

case "${1:-}" in
    "") ;;
    -h|--help) usage; exit 0 ;;
    *) usage >&2; exit 2 ;;
esac

for dependency in jq rsync ssh; do
    command -v "$dependency" >/dev/null 2>&1 || {
        printf 'Missing required command: %s\n' "$dependency" >&2
        exit 1
    }
done

[[ -r "$CONFIG" ]] || { printf 'Cannot read deployment config: %s\n' "$CONFIG" >&2; exit 1; }

SERVER="$(jq -er '.server' "$CONFIG")"
USER="$(jq -er '.user' "$CONFIG")"
SSH_PORT="$(jq -er '.ssh_port // 22' "$CONFIG")"
SSH_PASSWORD="$(jq -r '.ssh_password // empty' "$CONFIG")"
[[ "$SSH_PORT" =~ ^[0-9]+$ ]] && (( SSH_PORT > 0 && SSH_PORT < 65536 )) || {
    printf 'Invalid ssh_port in %s\n' "$CONFIG" >&2
    exit 1
}
HOST="$USER@$SERVER"

if [[ -n "$SSH_PASSWORD" ]]; then
    command -v sshpass >/dev/null 2>&1 || { printf 'Missing required command: sshpass\n' >&2; exit 1; }
    export SSHPASS="$SSH_PASSWORD"
    SSH_REMOTE="sshpass -e ssh -F /dev/null -p $SSH_PORT -o StrictHostKeyChecking=yes -o ConnectTimeout=15"
    ssh_remote() { sshpass -e ssh -F /dev/null -p "$SSH_PORT" -o StrictHostKeyChecking=yes -o ConnectTimeout=15 "$HOST" "$1"; }
else
    SSH_REMOTE="ssh -F /dev/null -p $SSH_PORT -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=15"
    ssh_remote() { ssh -F /dev/null -p "$SSH_PORT" -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=15 "$HOST" "$1"; }
fi
unset SSH_PASSWORD

RSYNC_OPTIONS=(-azc --no-owner --no-group --partial --info=progress2 --stats --timeout=30 --out-format='%i %n%L' -e "$SSH_REMOTE")

CHANGED=false
COMPOSER_CHANGED=false
LOG_DIR="$(mktemp -d "${TMPDIR:-/tmp}/numerimondes-deploy.XXXXXX")"
cleanup() {
    find "$LOG_DIR" -type f -delete 2>/dev/null || true
    find "$LOG_DIR" -depth -type d -empty -delete 2>/dev/null || true
}
trap cleanup EXIT

while IFS=$'\t' read -r NAME SOURCE DESTINATION TYPE; do
    [[ -n "$NAME" && -n "$SOURCE" && -n "$DESTINATION" ]] || continue
    if [[ "$SOURCE" = /* ]]; then
        SOURCE_DIR="$SOURCE"
    else
        SOURCE_DIR="$ROOT/$SOURCE"
    fi
    SOURCE_DIR="$(cd -- "$SOURCE_DIR" && pwd -P)"

    printf '\n== %s ==\n' "$NAME"
    printf '%s -> %s:%s\n' "$SOURCE_DIR" "$HOST" "$DESTINATION"
    ARGS=(rsync "${RSYNC_OPTIONS[@]}" --exclude='/.git/')

    if [[ "$TYPE" == webkernel ]]; then
        ARGS+=(
            --exclude='/.env'
            --exclude='/.env.*'
            --exclude='/node_modules/'
            --exclude='/runtime/cache/*'
            --exclude='/runtime/sessions/'
            --exclude='/storage/private/*'
            --exclude='/storage/databases/*'
            --exclude='/storage/backups/*'
            --exclude='/modules/webkernel/system-panel'
            --exclude='/modules/webkernel/system-installer'
            --exclude='/account/'
            --exclude='account/'
            --exclude='/.yas-deploy-once.json'
            --exclude='/web-sync.config.json'
            --exclude='/deploy.sh'
        )
    fi

    LOG="$LOG_DIR/$(printf '%s' "$NAME" | tr -cs '[:alnum:]_.-' '_').log"
    if ! "${ARGS[@]}" "$SOURCE_DIR/" "$HOST:$DESTINATION/" 2>&1 | tee "$LOG"; then
        printf 'rsync failed for %s\n' "$NAME" >&2
        exit 1
    fi
    if grep -qE '^[<>ch*]' "$LOG"; then
        CHANGED=true
        if [[ "$TYPE" == webkernel ]] && grep -qE '^[<>ch*].*(/|[[:space:]])composer\.(json|lock)([[:space:]]|$)' "$LOG"; then
            COMPOSER_CHANGED=true
        fi
    fi
done < <(jq -r '.deploys | to_entries[] | select((.value.enable // true) == true or (.value.enable // true) == 1) | [.key, .value.source, .value.dest, .value.type] | @tsv' "$CONFIG")

PRODUCTION_DEST="$(jq -er '.deploys.Production.dest' "$CONFIG")"
printf '\n== Remote production actions ==\n'
REMOTE_COMMAND="cd '$PRODUCTION_DEST' && rm -f app/resources/components/website/navigation.css app/resources/components/website/footer.css app/resources/css/studio/navigation.css && mkdir -p runtime/cache/deploy && VERSION=\$(date -u +%Y%m%dT%H%M%S)-\$(od -An -N6 -tx1 /dev/urandom | tr -d ' \\n') && printf '%s\\n' \"\$VERSION\" > runtime/cache/deploy/site-version.tmp && mv runtime/cache/deploy/site-version.tmp runtime/cache/deploy/site-version && printf 'Deploy version: %s\\n' \"\$VERSION\""
if [[ "$COMPOSER_CHANGED" == true ]]; then
    REMOTE_COMMAND+=" && composer install --no-dev --optimize-autoloader --no-interaction --no-progress"
fi
REMOTE_COMMAND+=" && ./platform deploy"
ssh_remote "$REMOTE_COMMAND"
if [[ "$COMPOSER_CHANGED" == true ]]; then
    printf 'Composer installed with --no-dev; deployment artifacts rebuilt and activated.\n'
elif [[ "$CHANGED" == true ]]; then
    printf 'Changed files synced; deployment artifacts rebuilt and activated.\n'
else
    printf 'No source files changed; production deployment artifacts were rebuilt and activated.\n'
fi
