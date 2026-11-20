#!/usr/bin/env bash
# Check that every image referenced by the lab is published before starting.
set -euo pipefail

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is not installed or not in PATH." >&2
  exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
  echo "Docker Compose v2 is required." >&2
  exit 1
fi

if [ ! -f .env ]; then
  echo "Missing .env. Run: cp .env.example .env" >&2
  exit 1
fi

images="$(docker compose config --images)"
if [ -z "$images" ]; then
  echo "No image references found in Compose." >&2
  exit 1
fi

failed=0
while IFS= read -r image; do
  [ -n "$image" ] || continue
  printf 'Checking %s ... ' "$image"
  if docker manifest inspect "$image" >/dev/null 2>&1; then
    echo "OK"
  else
    echo "NOT FOUND OR INACCESSIBLE"
    failed=1
  fi
done <<< "$images"

if [ "$failed" -ne 0 ]; then
  echo "At least one referenced image tag is unavailable. Check IMAGE_TAG in .env and the GHCR packages." >&2
  exit 1
fi
echo "All referenced image manifests are available."
