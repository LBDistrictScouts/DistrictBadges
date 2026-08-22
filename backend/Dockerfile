ARG PHP_VERSION=8.5
FROM php:${PHP_VERSION}-fpm-alpine
LABEL authors="Jacob Tyler, Letchworth, Baldock & Ashwell Scouts"
LABEL org.opencontainers.image.title="District Badges Backend"
LABEL org.opencontainers.image.description="CakePHP backend for the Letchworth, Baldock and Ashwell Scouts District Badges stock and ordering system."
LABEL org.opencontainers.image.source="https://github.com/LBDistrictScouts/DistrictBadges"

ARG user=www-data
ARG group=www-data

ENV TAR_OPTIONS="--no-same-owner"

WORKDIR /tmp

RUN apk update && \
    apk add --no-cache bash zip unzip nodejs npm postgresql17-client icu-dev libpq-dev

RUN npm install -g corepack && \
    corepack enable

RUN docker-php-ext-configure intl || true && \
    docker-php-ext-configure pgsql && \
    docker-php-ext-install intl pgsql pdo pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN mkdir -p /home/$user/.composer \
    && chown -R $user:$group /home/$user

USER $user

WORKDIR /var/www/html

COPY --chown=$user:$group composer.json .
COPY --chown=$user:$group composer.lock .

RUN composer install --optimize-autoloader --no-scripts --no-interaction --profile --prefer-dist

COPY --chown=$user:$group package.json .
COPY --chown=$user:$group yarn.lock .
COPY --chown=$user:$group .yarnrc.yml .

RUN yarn install --immutable

COPY --chown=$user:$group . .

RUN test ! -f config/app_local.php && \
    yarn build && \
    test -f webroot/js/vendor/jquery.min.js && \
    test -f webroot/js/vendor/select2.min.js && \
    test -f webroot/css/vendor/select2.min.css && \
    composer run-script post-install-cmd --no-interaction && \
    rm -f config/app_local.php && \
    ln -sf /dev/stdout logs/debug.log && \
    ln -sf /dev/stderr logs/error.log && \
    ln -sf /dev/stdout logs/cli-debug.log && \
    ln -sf /dev/stderr logs/cli-error.log && \
    test ! -f config/app_local.php
