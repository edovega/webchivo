# syntax=docker/dockerfile:1

# Build stage: seal (encrypted flags).
FROM debian:bookworm-slim AS seal-build
RUN apt-get update \
 && apt-get install -y --no-install-recommends gcc libc6-dev python3 \
 && rm -rf /var/lib/apt/lists/*
COPY seal/generate_seal.py /src/seal/
WORKDIR /build
RUN --mount=type=secret,id=ctf_flags,required=false \
    python3 /src/seal/generate_seal.py /build/seal.c \
 && gcc -O2 -s -static -o /build/seal /build/seal.c \
 && rm -f /build/seal.c

# Database image.
FROM postgres:9.6

COPY --from=seal-build /build/seal /opt/cib203/seal
COPY db/initdb/ /docker-entrypoint-initdb.d/
COPY db/entrypoint.sh /usr/local/bin/lab-db-entrypoint

RUN chown -R postgres:postgres /opt/cib203 \
 && chmod 0700 /opt/cib203 /opt/cib203/seal \
 && chmod 0644 /docker-entrypoint-initdb.d/*.sql \
 && chmod 0755 /docker-entrypoint-initdb.d/04_flags.sh /usr/local/bin/lab-db-entrypoint

ENTRYPOINT ["/usr/local/bin/lab-db-entrypoint"]
CMD ["postgres"]
