#!/usr/bin/env bash
# Maintainer check: no flag value from .env is in tracked files, git history or
# image history, and no local-only file is tracked.
# Prints only "flag N clean" or "flag N LEAKED". Never prints a value.
#
# Usage: seal/check_leaks.sh [image_tag]
set -euo pipefail

cd "$(git -C "$(dirname "$0")" rev-parse --show-toplevel)"
ENV_FILE="${ENV_FILE:-.env}"
[ -f "$ENV_FILE" ] || { echo "check_leaks: $ENV_FILE not found" >&2; exit 2; }

env_value() {
  grep -E "^$1=" "$ENV_FILE" | head -n 1 | cut -d= -f2- | tr -d '\r'
}

owner="$(env_value GHCR_OWNER)"
tag="${1:-$(env_value IMAGE_TAG)}"
status=0

tracked="$(git ls-files -- .env spec tests/smoke.sh ':(glob)*.md' ':(exclude)README.md')"
if [ -n "$tracked" ]; then
  echo "local-only files are tracked:"
  printf '  %s\n' $tracked
  status=1
fi

files_with_flag_lines="$(git grep -lE '^FLAG_0[1-8]=[^<]' || true)"
if [ -n "$files_with_flag_lines" ]; then
  echo "tracked files with FLAG_0N= lines:"
  printf '  %s\n' $files_with_flag_lines
  status=1
fi

for n in 1 2 3 4 5 6 7 8; do
  value="$(env_value "FLAG_0${n}")"
  if [ -z "$value" ]; then
    echo "flag $n MISSING in $ENV_FILE"
    status=1
    continue
  fi
  leaked=0
  git grep -qF -- "$value" && leaked=1
  [ -n "$(git log --all --oneline -S"$value" 2>/dev/null)" ] && leaked=1
  for component in app db reviewer; do
    image="ghcr.io/${owner}/cib203-lab-web-${component}:${tag}"
    if docker image inspect "$image" >/dev/null 2>&1; then
      docker history --no-trunc "$image" | grep -qF -- "$value" && leaked=1
    fi
  done
  if [ "$leaked" -eq 0 ]; then
    echo "flag $n clean"
  else
    echo "flag $n LEAKED"
    status=1
  fi
  unset value
done
exit "$status"
