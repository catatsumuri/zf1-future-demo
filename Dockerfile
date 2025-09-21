FROM php:8.1-apache AS base

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN set -eux; \
    a2enmod rewrite remoteip; \
    sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf; \
    sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}/!g' /etc/apache2/apache2.conf; \
    sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf; \
    printf '%s\n' \
        "SetEnvIf Request_URI \"^/(healthz|readyz|favicon\\.ico)$\" dontlog" \
        > /etc/apache2/conf-available/healthchecks-dontlog.conf; \
    a2enconf healthchecks-dontlog; \
    printf '%s\n' \
        'RemoteIPHeader X-Forwarded-For' \
        'RemoteIPTrustedProxy 10.0.0.0/8' \
        'RemoteIPTrustedProxy 172.16.0.0/12' \
        'RemoteIPTrustedProxy 192.168.0.0/16' \
        > /etc/apache2/conf-available/remoteip.conf; \
    a2enconf remoteip; \
    sed -ri 's#CustomLog \$\{APACHE_LOG_DIR\}/access.log combined#CustomLog ${APACHE_LOG_DIR}/access.log combined env=!dontlog#g' /etc/apache2/sites-available/*.conf; \
    docker-php-ext-install pdo_mysql

WORKDIR /var/www/html

FROM composer:2 AS composer-deps

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

COPY composer.json composer.lock ./
COPY application ./application

RUN composer install \
    --no-dev \
    --classmap-authoritative \
    --prefer-dist \
    --no-interaction \
    --no-progress

FROM base AS dev

# Intentionally rely on docker-compose bind mounts for source and vendor during development.

FROM base AS prod

ENV APPLICATION_ENV=production

COPY --from=composer-deps /app/vendor ./vendor
COPY . .
