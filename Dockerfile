ARG PHP_VERSION=8.4
ARG NODE_VERSION=20
ARG ALPINE_VERSION=3.22
ARG NGINX_VERSION=1.20
ARG APP_ENV=dev

# -------- 🟢 NODE BUILDER (pour prod) ----------
FROM node:20-alpine AS node-builder
WORKDIR /var/www/html
COPY package*.json ./
RUN npm install --frozen-lockfile
COPY assets/ ./assets
RUN npm run build

# -------- 🟡 PHP FPM ----------
FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} AS php

ARG APP_ENV=dev
ENV APP_ENV=${APP_ENV}

RUN apk update && apk add --no-cache \
    bash git zip unzip curl curl-dev \
    fontconfig jpegoptim libwebp optipng \
    dbus libx11 glib libxrender libxext libintl \
    ttf-dejavu ttf-droid ttf-freefont ttf-liberation \
    icu-dev libzip-dev libpng-dev libjpeg-turbo-dev \
    freetype-dev libwebp-dev oniguruma-dev libxml2-dev \
    libxslt-dev gmp-dev libgcrypt-dev $PHPIZE_DEPS \
    mysql-client

RUN docker-php-ext-configure intl \
 && docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
 && docker-php-ext-install -j$(nproc) \
    bcmath \
    intl gd pdo_mysql zip pcntl mbstring opcache xsl gmp curl exif

# Supprimer les dépendances inutiles pour réduire la taille de l'image
RUN apk del $PHPIZE_DEPS \
    curl-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    oniguruma-dev \
    libxml2-dev \
    libxslt-dev \
    gmp-dev \
    libgcrypt-dev \
    && apk add --no-cache gmp libxslt \
    && rm -rf /var/cache/apk/*

# COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1 \
    PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /var/www/html
COPY docker/php/php.ini /usr/local/etc/php/conf.d/php.ini
COPY docker/php/conf.d/opcache.${APP_ENV}.ini /usr/local/etc/php/conf.d/opcache.ini

RUN mkdir -p var && chown -R www-data:www-data var && chmod -R 775 var

EXPOSE 9000
CMD ["php-fpm"]

# -------- 🔵 NGINX ----------
ARG APP_ENV=dev
ENV APP_ENV=${APP_ENV}

# Stage dev : utilise Nginx officiel + code monté via volume
FROM nginx:${NGINX_VERSION}-alpine AS dev

# Stage prod : intègre les assets buildés par Webpack Encore
FROM nginx:${NGINX_VERSION}-alpine AS prod
COPY --from=node-builder /var/www/html/public/build /var/www/html/public/build
COPY docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf


EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]