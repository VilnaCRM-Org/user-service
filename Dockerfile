FROM composer/composer:2.8-bin AS composer
FROM mlocati/php-extension-installer:2.7 AS php_extension_installer

FROM dunglas/frankenphp:1.4-php8.4-alpine AS frankenphp_base

WORKDIR /srv/app

COPY --from=php_extension_installer --link /usr/bin/install-php-extensions /usr/local/bin/

RUN apk add --no-cache \
    acl=~2.3 \
    file=~5.46 \
    gettext=~0.22 \
    git=~2.47 \
    curl=~8.12 \
    autoconf=~2.72 \
    cyrus-sasl-dev=~2.1

ARG STABILITY=stable
ENV STABILITY=${STABILITY}

ARG SYMFONY_VERSION=""
ENV SYMFONY_VERSION=${SYMFONY_VERSION}

ARG APCU_VERSION=v5.1.28
ARG MONGODB_VERSION=2.2.1
ARG REDIS_VERSION=6.3.0
ARG APCU_SHA256=ca9c1820810a168786f8048a4c3f8c9e3fd941407ad1553259fb2e30b5f057bf
ARG MONGODB_SHA256=b923617bec3cde420d80bf78aeb05002be3c0e930b93adaacaa5c2e0c25adb42
ARG REDIS_SHA256=0d5141f634bd1db6c1ddcda053d25ecf2c4fc1c395430d534fd3f8d51dd7f0b5

ENV APP_ENV=prod

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

RUN set -eux; \
    apcu_src="$(mktemp -d)"; \
    mongodb_src="$(mktemp -d)"; \
    redis_src="$(mktemp -d)"; \
    apcu_tgz="${apcu_src}/apcu-${APCU_VERSION#v}.tgz"; \
    mongodb_tgz="${mongodb_src}/mongodb-${MONGODB_VERSION}.tgz"; \
    redis_tgz="${redis_src}/redis-${REDIS_VERSION}.tgz"; \
    curl -fsSLo "$apcu_tgz" "https://pecl.php.net/get/apcu-${APCU_VERSION#v}.tgz"; \
    echo "${APCU_SHA256}  ${apcu_tgz}" | sha256sum -c -; \
    tar -xzf "$apcu_tgz" -C "$apcu_src"; \
    mv "$apcu_src/package.xml" "$apcu_src/apcu-${APCU_VERSION#v}/package.xml"; \
    curl -fsSLo "$mongodb_tgz" "https://pecl.php.net/get/mongodb-${MONGODB_VERSION}.tgz"; \
    echo "${MONGODB_SHA256}  ${mongodb_tgz}" | sha256sum -c -; \
    tar -xzf "$mongodb_tgz" -C "$mongodb_src"; \
    mv "$mongodb_src/package.xml" "$mongodb_src/mongodb-${MONGODB_VERSION}/package.xml"; \
    curl -fsSLo "$redis_tgz" "https://pecl.php.net/get/redis-${REDIS_VERSION}.tgz"; \
    echo "${REDIS_SHA256}  ${redis_tgz}" | sha256sum -c -; \
    tar -xzf "$redis_tgz" -C "$redis_src"; \
    mv "$redis_src/package.xml" "$redis_src/redis-${REDIS_VERSION}/package.xml"; \
    install-php-extensions \
        @composer \
        "$apcu_src/apcu-${APCU_VERSION#v}" \
        intl \
        opcache \
        zip \
        "$mongodb_src/mongodb-${MONGODB_VERSION}" \
        openssl \
        xsl \
        "$redis_src/redis-${REDIS_VERSION}" \
    && rm -rf "$apcu_src" "$mongodb_src" "$redis_src" \
    && apk add --no-cache \
        icu-libs=~74.2 \
        libzip=~1.11 \
        libxslt=~1.1 \
        libsasl=~2.1 \
        snappy=~1.1

COPY --link infrastructure/docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/

COPY --link --chmod=755 infrastructure/docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
ENTRYPOINT ["docker-entrypoint"]

COPY --link infrastructure/docker/caddy/Caddyfile /etc/caddy/Caddyfile

HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1

COPY --from=composer --link /composer /usr/bin/composer

COPY --link composer.* symfony.* ./
RUN set -eux; \
    if [ -f composer.json ]; then \
        composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress; \
        composer clear-cache; \
    fi

COPY --link . ./
RUN rm -Rf infrastructure/docker/

# AC: NFR-61 - Enforce JWT key permissions (RC-03 fix)
RUN set -eux; \
    if [ -f config/jwt/private.pem ] && [ -f config/jwt/public.pem ]; then \
        chmod 600 config/jwt/private.pem; \
        chmod 644 config/jwt/public.pem; \
    fi

RUN set -eux; \
    mkdir -p var/cache var/log; \
    if [ -f composer.json ]; then \
        composer dump-autoload --classmap-authoritative --no-dev; \
        composer dump-env prod; \
        chmod +x bin/console; \
        sync; \
    fi

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

# Dev FrankenPHP image
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev \
    FRANKENPHP_CONFIG="import worker.Caddyfile" \
    XDEBUG_MODE=off

RUN apk add --no-cache \
    bash=~5.2 \
    make=~4.4 \
    bats=~1.11 \
    bc=~1.07

RUN curl --fail --location --show-error --silent \
    --retry 5 --retry-delay 2 --retry-max-time 120 \
    https://get.symfony.com/cli/installer \
    --output /tmp/symfony-installer \
 && bash /tmp/symfony-installer \
 && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony \
 && rm /tmp/symfony-installer

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN set -eux; \
    install-php-extensions xdebug

COPY --link infrastructure/docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/
COPY --link infrastructure/docker/php/worker.Caddyfile /etc/caddy/worker.Caddyfile

RUN git config --global --add safe.directory /srv/app

RUN rm -f .env.local.php

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch"]

# Prod FrankenPHP image
FROM frankenphp_base AS frankenphp_prod

ENV FRANKENPHP_CONFIG="import worker.Caddyfile"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link infrastructure/docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/
COPY --link infrastructure/docker/php/worker.Caddyfile /etc/caddy/worker.Caddyfile

COPY --link composer.* symfony.* ./
RUN set -eux; \
    composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

RUN rm -Rf infrastructure/docker/

# Worker image
FROM frankenphp_base AS app_workers

RUN apk add --no-cache supervisor=~4.2

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link infrastructure/docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/
COPY --link infrastructure/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
