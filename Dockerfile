# syntax=docker/dockerfile:1

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
COPY src ./src
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --classmap-authoritative --ignore-platform-reqs

FROM php:8.4-apache AS runtime

RUN a2enmod headers rewrite \
    && sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && docker-php-ext-install opcache

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-panel.ini"
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/panel
COPY --from=vendor /app/vendor ./vendor
COPY src ./src
COPY templates ./templates
COPY public ./public

RUN mkdir -p /var/lib/panel && chown www-data:www-data /var/lib/panel
ENV APP_STORAGE=/var/lib/panel

USER www-data
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s CMD ["php", "-r", "exit(@file_get_contents('http://127.0.0.1:8080/healthz') === 'ok' ? 0 : 1);"]
