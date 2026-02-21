FROM php:8.4-cli-alpine AS vendor
WORKDIR /app

RUN apk add --no-cache \
    bash \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --no-progress --prefer-dist --optimize-autoloader
COPY . .
RUN rm -f bootstrap/cache/*.php
RUN composer dump-autoload --optimize --no-scripts

FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

FROM php:8.4-fpm-alpine
WORKDIR /var/www/html
ARG APP_UID=1000
ARG APP_GID=1000

RUN apk add --no-cache \
    bash \
    curl \
    libzip-dev \
    libpng-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    oniguruma-dev \
    icu-dev \
    mariadb-client \
    sqlite \
    sqlite-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring intl zip gd

RUN addgroup -g "${APP_GID}" -S appgroup \
    && adduser -S -D -H -u "${APP_UID}" -G appgroup appuser

COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY deploy/docker/php/php.ini /usr/local/etc/php/conf.d/99-choyxona.ini
RUN rm -f /var/www/html/bootstrap/cache/*.php

RUN chown -R appuser:appgroup /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
