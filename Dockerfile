FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader
COPY . .
RUN composer dump-autoload --optimize

FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

FROM php:8.3-fpm-alpine
WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    curl \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    mariadb-client \
    sqlite \
    sqlite-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring intl zip

COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY deploy/docker/php/php.ini /usr/local/etc/php/conf.d/99-choyxona.ini

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
