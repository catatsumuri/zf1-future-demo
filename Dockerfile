FROM php:8.1-apache AS base

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN set -eux; \
    a2enmod rewrite; \
    sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf; \
    sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}/!g' /etc/apache2/apache2.conf; \
    sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

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

COPY --from=composer-deps /app/vendor ./vendor
COPY . .

FROM base AS prod

ENV APPLICATION_ENV=production

COPY --from=composer-deps /app/vendor ./vendor
COPY . .
