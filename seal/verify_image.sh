#!/usr/bin/env bash
# Maintainer check: the published images carry exactly the flags in .env.
# Prints only "flag N OK" or "flag N MISMATCH". Never prints a value.
#
# Usage: seal/verify_image.sh [image_tag] [--no-pull]
set -euo pipefail

cd "$(dirname "$0")/.."
ENV_FILE="${ENV_FILE:-.env}"
[ -f "$ENV_FILE" ] || { echo "verify_image: $ENV_FILE not found" >&2; exit 2; }

env_value() {
  grep -E "^$1=" "$ENV_FILE" | head -n 1 | cut -d= -f2- | tr -d '\r'
}

owner="$(env_value GHCR_OWNER)"
tag="$(env_value IMAGE_TAG)"
pull=1
for arg in "$@"; do
  case "$arg" in
    --no-pull) pull=0 ;;
    *) tag="$arg" ;;
  esac
done
[ -n "$owner" ] && [ -n "$tag" ] || { echo "verify_image: GHCR_OWNER or IMAGE_TAG missing" >&2; exit 2; }

db_image="ghcr.io/${owner}/cib203-lab-web-db:${tag}"
app_image="ghcr.io/${owner}/cib203-lab-web-app:${tag}"

if [ "$pull" -eq 1 ]; then
  docker pull -q "$db_image" >/dev/null
  docker pull -q "$app_image" >/dev/null
fi

status=0
for n in 1 2 3 4 5 6 7 8; do
  expected="$(env_value "FLAG_0${n}")"
  if [ -z "$expected" ]; then
    echo "flag $n MISSING in $ENV_FILE"
    status=1
    continue
  fi
  if [ "$n" -le 6 ]; then image="$db_image"; else image="$app_image"; fi
  actual="$(docker run --rm --pull never --entrypoint /opt/cib203/seal "$image" "$n" 2>/dev/null || true)"
  if [ "$actual" = "$expected" ]; then
    echo "flag $n OK"
  else
    echo "flag $n MISMATCH"
    status=1
  fi
  unset expected actual
done
exit "$status"
