#!/bin/sh
# db container entrypoint wrapper. Runs as root.
# On an existing data volume the init scripts do not run again, so the seal
# binary is removed here. On the first start it is left for 04_flags.sh.
set -eu

PGDATA="${PGDATA:-/var/lib/postgresql/data}"
if [ -s "$PGDATA/PG_VERSION" ]; then
  rm -f /opt/cib203/seal
fi

exec docker-entrypoint.sh "$@"
