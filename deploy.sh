#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
CONFIG="${WEB_SYNC_CONFIG:-$ROOT/web-sync.config.json}"
VERIFY_CONTENT=false

usage() {
    printf 'Usage: %s [--verify-content]\n' "${0##*/}"
    printf '  Default: fast incremental rsync based on file timestamps.\n'
    printf '  --verify-content: compare file contents with checksums.\n'
}

case "${1:-}" in
    "") ;;
    --verify-content) VERIFY_CONTENT=true ;;
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
    SSH_REMOTE="sshpass -e ssh -p $SSH_PORT -o StrictHostKeyChecking=yes -o ConnectTimeout=15"
    ssh_remote() { sshpass -e ssh -p "$SSH_PORT" -o StrictHostKeyChecking=yes -o ConnectTimeout=15 "$HOST" "$1"; }
else
    SSH_REMOTE="ssh -p $SSH_PORT -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=15"
    ssh_remote() { ssh -p "$SSH_PORT" -o BatchMode=yes -o StrictHostKeyChecking=yes -o ConnectTimeout=15 "$HOST" "$1"; }
fi
unset SSH_PASSWORD

RSYNC_OPTIONS=(-az --no-owner --no-group --partial --info=progress2 --stats --timeout=30 --out-format='%i %n%L' -e "$SSH_REMOTE")
if [[ "$VERIFY_CONTENT" == true ]]; then
    RSYNC_OPTIONS+=(--checksum)
fi

CHANGED=false
LOG_DIR="$(mktemp -d "${TMPDIR:-/tmp}/numerimondes-deploy.XXXXXX")"
STATE_FILE="$ROOT/runtime/cache/deploy/.deploy-last-success"
mkdir -p "$(dirname -- "$STATE_FILE")"
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

    FILE_LIST="$LOG_DIR/$(printf '%s' "$NAME" | tr -cs '[:alnum:]_.-' '_').files"
    if [[ -f "$STATE_FILE" && "$VERIFY_CONTENT" == false ]]; then
        if [[ "$TYPE" == webkernel ]]; then
            (cd -- "$SOURCE_DIR" && find . \
                -type d \( -name .git -o -name node_modules -o -name cache -path './runtime/cache' \
                    -o -path './runtime/sessions' -o -path './storage/private' \
                    -o -path './storage/databases' -o -path './storage/backups' \
                    -o -path './modules/webkernel/system-panel' -o -path './modules/webkernel/system-installer' \
                    -o -name account \) -prune -o \
                \( -type f -o -type l \) -newer "$STATE_FILE" \
                ! -name '.env' ! -name '.env.*' ! -name 'web-sync.config.json' \
                ! -name 'deploy.sh' ! -name '.yas-deploy-once.json' -print0) > "$FILE_LIST"
        else
            (cd -- "$SOURCE_DIR" && find . -type d -name .git -prune -o \
                \( -type f -o -type l \) -newer "$STATE_FILE" -print0) > "$FILE_LIST"
        fi
        if [[ ! -s "$FILE_LIST" ]]; then
            printf 'No changed source files; skipped.\n'
            continue
        fi
        ARGS+=(--from0 "--files-from=$FILE_LIST" --ignore-times)
    else
        ARGS+=(--checksum)
    fi

    LOG="$LOG_DIR/$(printf '%s' "$NAME" | tr -cs '[:alnum:]_.-' '_').log"
    if ! "${ARGS[@]}" "$SOURCE_DIR/" "$HOST:$DESTINATION/" 2>&1 | tee "$LOG"; then
        printf 'rsync failed for %s\n' "$NAME" >&2
        exit 1
    fi
    if grep -qE '^[<>ch*]' "$LOG"; then
        CHANGED=true
    fi
done < <(jq -r '.deploys | to_entries[] | select((.value.enable // true) == true or (.value.enable // true) == 1) | [.key, .value.source, .value.dest, .value.type] | @tsv' "$CONFIG")

if [[ "$CHANGED" == false ]]; then
    printf '\nNo files changed; remote Composer install and cache refresh skipped.\n'
    touch "$STATE_FILE"
    exit 0
fi

PRODUCTION_DEST="$(jq -er '.deploys.Production.dest' "$CONFIG")"
printf '\n== Remote production actions ==\n'
REMOTE_COMMAND="cd '$PRODUCTION_DEST' && composer install --no-dev --optimize-autoloader --no-interaction --no-progress && find runtime/cache/views -type f -delete && find runtime/cache/views -depth -type d -empty -delete && mkdir -p runtime/cache/views && php -r 'if (is_file(\"runtime/cache/compiled_routes.php\")) { unlink(\"runtime/cache/compiled_routes.php\"); }' && ./platform route:list >/dev/null"
ssh_remote "$REMOTE_COMMAND"
touch "$STATE_FILE"
printf 'Composer installed with --no-dev; compiled views and routes refreshed.\n'
