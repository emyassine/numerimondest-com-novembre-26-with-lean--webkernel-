#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
CONFIG="${WEB_SYNC_CONFIG:-$ROOT/web-sync.config.json}"

# ----------------------------- Colors -----------------------------
use_color() { [[ -t "$1" && -z "${NO_COLOR:-}" ]]; }
if use_color 1; then
    RESET=$'\033[0m'; BOLD=$'\033[1m'; DIM=$'\033[2m'
    RED=$'\033[31m'; GREEN=$'\033[32m'; YELLOW=$'\033[33m'
    BLUE=$'\033[34m'; MAGENTA=$'\033[35m'; CYAN=$'\033[36m'
else
    RESET=''; BOLD=''; DIM=''; RED=''; GREEN=''; YELLOW=''
    BLUE=''; MAGENTA=''; CYAN=''
fi

# -------------------------- Output helpers --------------------------
TOTAL_STEPS=1
step() { printf '\n%s%s[%s/%s]%s %s%s%s\n' "$BOLD" "$BLUE" "$1" "$TOTAL_STEPS" "$RESET" "$BOLD" "$2" "$RESET"; }
ok()   { printf '  %s[OK]%s %s\n' "$GREEN$BOLD" "$RESET" "$*"; }
info() { printf '  %s[i]%s  %s\n' "$CYAN$BOLD" "$RESET" "$*"; }
warn() { printf '  %s[!]%s  %s\n' "$YELLOW$BOLD" "$RESET" "$*"; }
dim()  { printf '      %s%s%s\n' "$DIM" "$*" "$RESET"; }
err()  { printf '  %s[ERROR]%s %s\n' "$BOLD$RED" "$RESET" "$*" >&2; }

print_remote_log() {
    local log=$1 mode=${2:-dim} line
    while IFS= read -r line; do
        if [[ "$mode" == error ]]; then
            printf '      %s%s%s\n' "$RED" "$line" "$RESET" >&2
        else
            dim "$line"
        fi
    done < <(
        sed -E \
            -e $'s/\033\\[[0-9;]*[[:alpha:]]//g' \
            -e 's/^([[:space:]]*)INFO[[:space:]]+/\1[INFO] /' \
            -e 's/^([[:space:]]*)OK[[:space:]]+/\1[OK] /' \
            "$log"
    )
}

usage() {
    cat <<EOF
Usage:
  ${0##*/}  Sync the configured sources, then rebuild production artifacts
  ${0##*/} --help  Show this help
EOF
}

case "${1:-}" in
    "") ;;
    -h|--help) usage; exit 0 ;;
    *) usage >&2; exit 2 ;;
esac

for dependency in jq rsync ssh; do
    command -v "$dependency" >/dev/null 2>&1 || {
        err "Missing required command: $dependency"
        exit 1
    }
done

[[ -r "$CONFIG" ]] || { err "Cannot read deployment config: $CONFIG"; exit 1; }

SERVER="$(jq -er '.server' "$CONFIG")"
USER="$(jq -er '.user' "$CONFIG")"
SSH_PORT="$(jq -er '.ssh_port // 22' "$CONFIG")"
SSH_PASSWORD="$(jq -r '.ssh_password // empty' "$CONFIG")"
[[ "$SSH_PORT" =~ ^[0-9]+$ ]] && (( SSH_PORT > 0 && SSH_PORT < 65536 )) || {
    err "Invalid ssh_port in $CONFIG"
    exit 1
}
HOST="$USER@$SERVER"

if [[ -n "$SSH_PASSWORD" ]]; then
    command -v sshpass >/dev/null 2>&1 || { err 'Missing required command: sshpass'; exit 1; }
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
DEPLOY_COUNT="$(jq -r '[.deploys | to_entries[] | select((.value.enable // true) == true or (.value.enable // true) == 1)] | length' "$CONFIG")"
TOTAL_STEPS=$((DEPLOY_COUNT + 2))
LOG_DIR="$(mktemp -d "${TMPDIR:-/tmp}/numerimondes-deploy.XXXXXX")"
ACTIVE_PID=''

cleanup() {
    if [[ -n "$ACTIVE_PID" ]]; then
        kill "$ACTIVE_PID" 2>/dev/null || true
    fi
    [[ -t 1 ]] && printf '\033[?25h'
    find "$LOG_DIR" -type f -delete 2>/dev/null || true
    find "$LOG_DIR" -depth -type d -empty -delete 2>/dev/null || true
}
trap cleanup EXIT
trap 'printf "\n"; warn "Interrupted."; exit 130' INT TERM

run_with_spinner() {
    local label=$1 log=$2 status i=0
    shift 2
    "$@" >"$log" 2>&1 &
    ACTIVE_PID=$!
    if [[ -t 1 ]]; then
        local frames=('|' '/' '-' '\\')
        printf '\033[?25l'
        while kill -0 "$ACTIVE_PID" 2>/dev/null; do
            printf '\r  %s%s%s %s' "$CYAN" "${frames[i % 4]}" "$RESET" "$label"
            i=$((i + 1))
            sleep 0.1
        done
        printf '\r\033[K\033[?25h'
    else
        info "$label"
    fi
    if wait "$ACTIVE_PID"; then status=0; else status=$?; fi
    ACTIVE_PID=''
    return "$status"
}

print_sync_summary() {
    local log=$1 max=20 count=0 total code color label path line
    total="$(grep -cE '^[<>ch*]' "$log" || true)"
    while IFS= read -r line; do
        [[ "$line" =~ ^([\<\>ch\*])[^[:space:]]*[[:space:]]+(.*)$ ]] || continue
        code=${BASH_REMATCH[1]}
        path=${BASH_REMATCH[2]}
        color="$YELLOW"
        label='M'
        case "$code" in
            '>'|'<') color="$GREEN"; label='SYNC' ;;
            c) color="$YELLOW"; label='META' ;;
            h) color="$CYAN"; label='LINK' ;;
            '*') color="$RED"; label='DEL' ;;
        esac
        printf '      %s%s%s  %s\n' "$color$BOLD" "$label" "$RESET" "$path"
        count=$((count + 1))
        if (( count >= max )); then
            local remaining=$((total - max))
            (( remaining > 0 )) && dim "... and $remaining more changed file(s)"
            break
        fi
    done <"$log"
    if (( total == 0 )); then
        ok 'No changed source files.'
    else
        ok "Synced $total changed file(s)."
    fi
    while IFS= read -r line; do dim "$line"; done < <(grep -E '^(Number of files|Number of created files|Number of deleted files|Total file size|Total transferred file size|sent |received |speedup is)' "$log" || true)
}

# ------------------------------- Header -------------------------------
printf '\n%s%s+-- Numerimondes : deploy --+%s\n' "$BOLD" "$MAGENTA" "$RESET"
printf '  %sProject %s %s\n' "$DIM" "$RESET" "$(basename "$ROOT")"
printf '  %sTarget  %s %s%s%s (SSH port %s)\n' "$DIM" "$RESET" "$BOLD$GREEN" "$HOST" "$RESET" "$SSH_PORT"
printf '  %sConfig  %s %s\n' "$DIM" "$RESET" "${CONFIG#"$ROOT"/}"

STEP_NUMBER=1
step "$STEP_NUMBER" 'Building local frontend assets'
LOCAL_BUILD_LOG="$LOG_DIR/local-assets.log"
if ! run_with_spinner 'Synchronizing and building frontend assets...' "$LOCAL_BUILD_LOG" "$ROOT/platform" assets:sync; then
    err 'Local frontend asset build failed; deployment was not started.'
    print_remote_log "$LOCAL_BUILD_LOG" error
    exit 1
fi
print_remote_log "$LOCAL_BUILD_LOG"
ok 'Frontend assets are ready to include in the deployment.'

STEP_NUMBER=1
while IFS=$'\t' read -r NAME SOURCE DESTINATION TYPE; do
    [[ -n "$NAME" && -n "$SOURCE" && -n "$DESTINATION" ]] || continue
    STEP_NUMBER=$((STEP_NUMBER + 1))
    if [[ "$SOURCE" = /* ]]; then SOURCE_DIR="$SOURCE"; else SOURCE_DIR="$ROOT/$SOURCE"; fi
    SOURCE_DIR="$(cd -- "$SOURCE_DIR" && pwd -P)"

    step "$STEP_NUMBER" "Syncing $NAME"
    info "$SOURCE_DIR -> $HOST:$DESTINATION/"
    ARGS=(rsync "${RSYNC_OPTIONS[@]}" --exclude='/.git/' --exclude='node_modules/')
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
    if ! run_with_spinner "Transferring $NAME..." "$LOG" "${ARGS[@]}" "$SOURCE_DIR/" "$HOST:$DESTINATION/"; then
        err "rsync failed for $NAME."
        while IFS= read -r line; do printf '      %s%s%s\n' "$RED" "$line" "$RESET" >&2; done <"$LOG"
        exit 1
    fi
    print_sync_summary "$LOG"
    if grep -qE '^[<>ch*]' "$LOG"; then
        CHANGED=true
        if [[ "$TYPE" == webkernel ]] && grep -qE '^[<>ch*].*(/|[[:space:]])composer\.(json|lock)([[:space:]]|$)' "$LOG"; then
            COMPOSER_CHANGED=true
        fi
    fi

    for MANAGED_DIRECTORY in app config; do
        [[ -d "$SOURCE_DIR/$MANAGED_DIRECTORY" ]] || continue
        MANAGED_LOG="$LOG_DIR/$(printf '%s_%s' "$NAME" "$MANAGED_DIRECTORY" | tr -cs '[:alnum:]_.-' '_').log"
        MANAGED_ARGS=(rsync "${RSYNC_OPTIONS[@]}" --exclude='/.git/' --exclude='node_modules/' --delete)
        info "Mirroring managed source directory: $MANAGED_DIRECTORY/"
        if ! run_with_spinner "Syncing $MANAGED_DIRECTORY/ exactly..." "$MANAGED_LOG" "${MANAGED_ARGS[@]}" "$SOURCE_DIR/$MANAGED_DIRECTORY/" "$HOST:$DESTINATION/$MANAGED_DIRECTORY/"; then
            err "rsync failed for managed source directory $MANAGED_DIRECTORY/."
            while IFS= read -r line; do printf '      %s%s%s\n' "$RED" "$line" "$RESET" >&2; done <"$MANAGED_LOG"
            exit 1
        fi
        print_sync_summary "$MANAGED_LOG"
        if grep -qE '^[<>ch*]' "$MANAGED_LOG"; then CHANGED=true; fi
    done
done < <(jq -r '.deploys | to_entries[] | select((.value.enable // true) == true or (.value.enable // true) == 1) | [.key, .value.source, .value.dest, .value.type] | @tsv' "$CONFIG")

STEP_NUMBER=$((STEP_NUMBER + 1))
PRODUCTION_DEST="$(jq -er '.deploys.Production.dest' "$CONFIG")"
step "$STEP_NUMBER" 'Rebuilding production'
info "Production path: $HOST:$PRODUCTION_DEST"
REMOTE_COMMAND="cd '$PRODUCTION_DEST'"
if [[ "$COMPOSER_CHANGED" == true ]]; then
    REMOTE_COMMAND+=" && composer install --no-dev --optimize-autoloader --no-interaction --no-progress"
fi
REMOTE_COMMAND+=" && composer reinstall 'webkernel/*' --no-interaction --no-progress"
REMOTE_COMMAND+=" && NO_COLOR=1 TERM=dumb APP_URL=https://numerimondes.com/ ./platform deploy:prod"
REMOTE_COMMAND+=" && mkdir -p runtime/cache/deploy && VERSION=\$(date -u +%Y%m%dT%H%M%S)-\$(od -An -N6 -tx1 /dev/urandom | tr -d ' \\n') && printf '%s\\n' \"\$VERSION\" > runtime/cache/deploy/site-version.tmp && mv runtime/cache/deploy/site-version.tmp runtime/cache/deploy/site-version && printf 'Deploy version: %s\\n' \"\$VERSION\""
REMOTE_LOG="$LOG_DIR/production.log"
if ! run_with_spinner 'Running production deployment actions...' "$REMOTE_LOG" ssh_remote "$REMOTE_COMMAND"; then
    err 'Remote production deployment failed.'
    print_remote_log "$REMOTE_LOG" error
    exit 1
fi
print_remote_log "$REMOTE_LOG"

if [[ "$COMPOSER_CHANGED" == true ]]; then
    ok 'Composer installed with --no-dev; deployment artifacts rebuilt and activated.'
elif [[ "$CHANGED" == true ]]; then
    ok 'Changed files synced; deployment artifacts rebuilt and activated.'
else
    ok 'No source changes; production deployment artifacts rebuilt and activated.'
fi
printf '\n%s%s[ALL DONE]%s\n\n' "$BOLD" "$GREEN" "$RESET"
