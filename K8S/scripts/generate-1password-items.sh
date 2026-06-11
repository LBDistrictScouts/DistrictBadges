#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
K8S_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

DRY_RUN=false
FORCE=false
ENVIRONMENT=""

REQUIRED_KEYS=(
  "SECURITY_SALT"
  "DATABASE_URL"
  "POSTGRES_DB"
  "POSTGRES_USER"
  "POSTGRES_PASSWORD"
  "EMAIL_TRANSPORT_DEFAULT_URL"
  "AWS_ACCESS_KEY_ID"
  "AWS_SECRET_ACCESS_KEY"
  "SQS_ORDER_QUEUE_URL"
  "SQS_BADGE_IMPORT_QUEUE_URL"
  "ALGOLIA_APP_ID"
  "ALGOLIA_ADMIN_API_KEY"
)

usage() {
  cat <<'EOF'
Generate/update 1Password items used by the K8S OnePasswordItem manifests.

Usage:
  ./K8S/scripts/generate-1password-items.sh [--env base|test|prod|all] [--dry-run] [--force]

Options:
  --env      Target environment item(s): base, test, prod, or all.
  --dry-run  Print actions without writing to 1Password.
  --force    Skip confirmation prompts.
  -h, --help Show this help text.

Notes:
  - Requires 1Password CLI (op) and an authenticated session.
  - Discovers item paths from:
      K8S/base/secrets/one-password-item.yaml
      K8S/overlays/*/one-password-item-patch.yaml
EOF
}

log() {
  printf '%s\n' "$*"
}

error() {
  printf 'Error: %s\n' "$*" >&2
}

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    error "Missing required command: $1"
    exit 1
  fi
}

rand_hex() {
  local bytes="$1"
  openssl rand -hex "$bytes"
}

confirm() {
  local prompt="$1"
  if [ "$FORCE" = true ]; then
    return 0
  fi

  local reply
  read -r -p "$prompt [y/N]: " reply
  case "$reply" in
    [yY]|[yY][eE][sS]) return 0 ;;
    *) return 1 ;;
  esac
}

discover_item_paths() {
  local manifests=(
    "${K8S_DIR}/base/secrets/one-password-item.yaml"
    "${K8S_DIR}/overlays/test/one-password-item-patch.yaml"
    "${K8S_DIR}/overlays/prod/one-password-item-patch.yaml"
  )

  local manifest
  for manifest in "${manifests[@]}"; do
    [ -f "$manifest" ] || continue
    sed -nE 's/^[[:space:]]*itemPath:[[:space:]]*"?([^"#]+)"?.*/\1/p' "$manifest"
  done
}

extract_vault_item() {
  local item_path="$1"
  if [[ "$item_path" =~ ^vaults/([^/]+)/items/(.+)$ ]]; then
    printf '%s\t%s\n' "${BASH_REMATCH[1]}" "${BASH_REMATCH[2]}"
    return 0
  fi
  return 1
}

default_postgres_host_for_item() {
  local item="$1"
  case "$item" in
    *" Prod"*) printf 'prod-district-badges-postgres' ;;
    *" Test"*) printf 'test-district-badges-postgres' ;;
    *) printf 'district-badges-postgres' ;;
  esac
}

environment_for_item_name() {
  local item="$1"
  case "$item" in
    *" Prod"*) printf 'prod' ;;
    *" Test"*) printf 'test' ;;
    *) printf 'base' ;;
  esac
}

should_process_item() {
  local item="$1"
  local item_env
  item_env="$(environment_for_item_name "$item")"

  if [ "$ENVIRONMENT" = "all" ]; then
    return 0
  fi

  [ "$item_env" = "$ENVIRONMENT" ]
}

prompt_for_environment() {
  local env
  while true; do
    env="$(prompt_with_default "Environment (base/test/prod/all)" "test")"
    case "$env" in
      base|test|prod|all)
        ENVIRONMENT="$env"
        return 0
        ;;
      *)
        error "Invalid environment '${env}'. Choose base, test, prod, or all."
        ;;
    esac
  done
}

prompt_with_default() {
  local label="$1"
  local default_value="$2"
  local value

  if [ -n "$default_value" ]; then
    read -r -p "${label} [${default_value}]: " value
    printf '%s\n' "${value:-$default_value}"
  else
    read -r -p "${label}: " value
    printf '%s\n' "$value"
  fi
}

upsert_item() {
  local vault="$1"
  local item="$2"

  local db_name db_user db_pass security_salt smtp_url
  local aws_key aws_secret sqs_order sqs_badge algolia_app algolia_key
  local db_host db_url

  db_name="$(prompt_with_default "POSTGRES_DB" "district_badges")"
  db_user="$(prompt_with_default "POSTGRES_USER" "district_badges")"
  db_pass="$(prompt_with_default "POSTGRES_PASSWORD" "$(rand_hex 16)")"
  security_salt="$(prompt_with_default "SECURITY_SALT" "$(rand_hex 32)")"

  db_host="$(default_postgres_host_for_item "$item")"
  db_url="postgres://${db_user}:${db_pass}@${db_host}:5432/${db_name}?encoding=utf8&timezone=UTC&cacheMetadata=true"
  db_url="$(prompt_with_default "DATABASE_URL" "$db_url")"

  smtp_url="$(prompt_with_default "EMAIL_TRANSPORT_DEFAULT_URL" "")"
  aws_key="$(prompt_with_default "AWS_ACCESS_KEY_ID" "")"
  aws_secret="$(prompt_with_default "AWS_SECRET_ACCESS_KEY" "")"
  sqs_order="$(prompt_with_default "SQS_ORDER_QUEUE_URL" "")"
  sqs_badge="$(prompt_with_default "SQS_BADGE_IMPORT_QUEUE_URL" "")"
  algolia_app="$(prompt_with_default "ALGOLIA_APP_ID" "")"
  algolia_key="$(prompt_with_default "ALGOLIA_ADMIN_API_KEY" "")"

  local op_fields=(
    "SECURITY_SALT[text]=${security_salt}"
    "DATABASE_URL[text]=${db_url}"
    "POSTGRES_DB[text]=${db_name}"
    "POSTGRES_USER[text]=${db_user}"
    "POSTGRES_PASSWORD[password]=${db_pass}"
    "EMAIL_TRANSPORT_DEFAULT_URL[text]=${smtp_url}"
    "AWS_ACCESS_KEY_ID[text]=${aws_key}"
    "AWS_SECRET_ACCESS_KEY[password]=${aws_secret}"
    "SQS_ORDER_QUEUE_URL[text]=${sqs_order}"
    "SQS_BADGE_IMPORT_QUEUE_URL[text]=${sqs_badge}"
    "ALGOLIA_APP_ID[text]=${algolia_app}"
    "ALGOLIA_ADMIN_API_KEY[password]=${algolia_key}"
  )

  log ""
  log "Item: ${item} (vault: ${vault})"

  local exists=false
  if op item get "$item" --vault "$vault" >/dev/null 2>&1; then
    exists=true
  fi

  if [ "$DRY_RUN" = true ]; then
    if [ "$exists" = true ]; then
      log "[dry-run] Would update item ${item} in vault ${vault}."
    else
      log "[dry-run] Would create item ${item} in vault ${vault}."
    fi
    return 0
  fi

  if [ "$exists" = true ]; then
    if confirm "Update existing item '${item}' in vault '${vault}'?"; then
      op item edit "$item" --vault "$vault" "${op_fields[@]}" >/dev/null
      log "Updated: ${vault}/${item}"
    else
      log "Skipped update: ${vault}/${item}"
    fi
  else
    if confirm "Create item '${item}' in vault '${vault}'?"; then
      op item create --vault "$vault" --category "Secure Note" --title "$item" "${op_fields[@]}" >/dev/null
      log "Created: ${vault}/${item}"
    else
      log "Skipped create: ${vault}/${item}"
    fi
  fi
}

while (($# > 0)); do
  case "$1" in
    --env)
      shift
      if [ $# -eq 0 ]; then
        error "Missing value for --env"
        usage
        exit 1
      fi
      case "$1" in
        base|test|prod|all)
          ENVIRONMENT="$1"
          ;;
        *)
          error "Invalid value for --env: $1"
          usage
          exit 1
          ;;
      esac
      ;;
    --dry-run)
      DRY_RUN=true
      ;;
    --force)
      FORCE=true
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      error "Unknown argument: $1"
      usage
      exit 1
      ;;
  esac
  shift
done

require_cmd op
require_cmd openssl

if ! op whoami >/dev/null 2>&1; then
  error "No active 1Password CLI session found. Run: op signin"
  exit 1
fi

declare -a targets=()

while IFS= read -r item_path; do
  [ -n "$item_path" ] || continue
  if ! pair="$(extract_vault_item "$item_path")"; then
    error "Skipping unsupported itemPath format: $item_path"
    continue
  fi
  if printf '%s\n' "${targets[@]}" | grep -Fx -- "$pair" >/dev/null 2>&1; then
    continue
  fi
  targets+=("$pair")
done < <(discover_item_paths)

if [ ${#targets[@]} -eq 0 ]; then
  error "No OnePassword item paths found in manifests."
  exit 1
fi

if [ -z "$ENVIRONMENT" ]; then
  if [ "$FORCE" = true ]; then
    ENVIRONMENT="all"
  else
    prompt_for_environment
  fi
fi

log "Discovered ${#targets[@]} item(s) from K8S manifests."
log "Selected environment: ${ENVIRONMENT}"
log "Required keys: ${REQUIRED_KEYS[*]}"

for target in "${targets[@]}"; do
  IFS=$'\t' read -r vault item <<< "$target"
  if ! should_process_item "$item"; then
    continue
  fi
  upsert_item "$vault" "$item"
done

log ""
log "Done."