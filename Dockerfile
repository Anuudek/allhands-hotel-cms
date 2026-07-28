# ---- PHP dependencies ----
FROM composer:2 AS composer_build
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---- Frontend assets ----
FROM node:22-alpine AS node_build
WORKDIR /app
COPY --from=composer_build /app /app
RUN npm ci && npm run build:atom

# ---- PHP-FPM runtime ----
FROM php:8.5-fpm-alpine AS cms-fpm
RUN apk add --no-cache icu-dev oniguruma-dev libzip-dev netcat-openbsd linux-headers \
    && docker-php-ext-install pdo_mysql mbstring intl zip sockets
WORKDIR /var/www/html
COPY --from=node_build /app /var/www/html
COPY .env.docker /var/www/html/.env.docker
COPY docker/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# ---- Nginx runtime (static files + fastcgi to cms-fpm) ----
FROM nginx:alpine AS cms-nginx
COPY --from=node_build /app/public /var/www/html/public
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
