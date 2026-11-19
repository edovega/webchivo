# syntax=docker/dockerfile:1

# Build stage: seal (encrypted flags) and the setuid flag reader.
FROM debian:bookworm-slim AS seal-build
RUN apt-get update \
 && apt-get install -y --no-install-recommends gcc libc6-dev python3 \
 && rm -rf /var/lib/apt/lists/*
COPY seal/generate_seal.py seal/rce_reader.c /src/seal/
WORKDIR /build
RUN --mount=type=secret,id=ctf_flags,required=false \
    python3 /src/seal/generate_seal.py /build/seal.c \
 && gcc -O2 -s -static -o /build/seal /build/seal.c \
 && rm -f /build/seal.c
RUN gcc -O2 -s -static -o /build/rce_flag /src/seal/rce_reader.c

# Application image.
FROM php:8.1-apache-bookworm

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install pgsql pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

COPY app/apache/lab.conf /etc/apache2/conf-available/lab.conf
RUN a2enconf lab

COPY app/php/ /opt/cib203/php/
COPY app/html/ /var/www/html/
COPY app/entrypoint.sh /usr/local/bin/lab-entrypoint
COPY --from=seal-build /build/seal /opt/cib203/seal
COPY --from=seal-build /build/rce_flag /usr/local/bin/rce_flag

RUN chown -R root:root /var/www/html /opt/cib203 \
 && find /var/www/html -type d -exec chmod 0755 {} + \
 && find /var/www/html -type f -exec chmod 0644 {} + \
 && chown www-data:www-data /var/www/html/uploads \
 && chown root:root /var/www/html/uploads/.htaccess \
 && chmod 0700 /opt/cib203/seal \
 && chmod 4711 /usr/local/bin/rce_flag \
 && chmod 0755 /usr/local/bin/lab-entrypoint \
 && mkdir -p /var/www/private \
 && chown root:www-data /var/www/private \
 && chmod 0750 /var/www/private \
 && chmod 0700 /root

ENTRYPOINT ["/usr/local/bin/lab-entrypoint"]
CMD ["apache2-foreground"]
