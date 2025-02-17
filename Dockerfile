FROM composer/composer:2-bin AS composer
FROM mlocati/php-extension-installer:2.2 AS php_extension_installer

FROM dunglas/frankenphp:1.4.2-php8.3 AS frankenphp_base

WORKDIR /srv/app

COPY --from=php_extension_installer --link /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update && apt-get install --no-install-recommends -y \
    acl \
    file \
    gettext \
    git \
    curl \
 && rm -rf /var/lib/apt/lists/*

ARG STABILITY=stable
ENV STABILITY=${STABILITY}

ARG SYMFONY_VERSION=""
ENV SYMFONY_VERSION=${SYMFONY_VERSION}

ENV APP_ENV=prod

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

RUN set -eux; \
    install-php-extensions \
        apcu \
        intl \
        opcache \
        zip \
        pdo_mysql \
        redis-6.0.2 \
        openssl \
        xsl

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
    XDEBUG_MODE=off

RUN apt-get update && apt-get install --no-install-recommends -y \
    bash \
    make \
 && rm -rf /var/lib/apt/lists/*

RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
 && apt-get update && apt-get install --no-install-recommends -y symfony-cli \
 && rm -rf /var/lib/apt/lists/*

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN set -eux; \
    install-php-extensions xdebug

COPY --link infrastructure/docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

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

RUN apt-get update && apt-get install --no-install-recommends -y supervisor \
 && rm -rf /var/lib/apt/lists/*

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link infrastructure/docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/
COPY --link infrastructure/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]
