#syntax=docker/dockerfile:1.4

# Adapted from https://github.com/dunglas/symfony-docker


# Versions
FROM dunglas/frankenphp:1-php8.3 AS frankenphp_upstream


# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target


# Base FrankenPHP image
FROM frankenphp_upstream AS frankenphp_base

WORKDIR /app

# persistent / runtime deps
# hadolint ignore=DL3008
RUN apt-get update && apt-get install --no-install-recommends -y \
	acl \
	file \
	gettext \
	git \
    systemd \
    systemd-sysv \
	&& rm -rf /var/lib/apt/lists/*

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN set -eux; \
	install-php-extensions \
		@composer \
		apcu \
		intl \
		opcache \
		zip \
        pdo_mysql \
        redis \
        openssl \
        xsl \
	;

###> recipes ###
###< recipes ###

COPY --link infrastructure/docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY --link --chmod=700 infrastructure/docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link infrastructure/docker/caddy/Caddyfile /etc/caddy/Caddyfile

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1

CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile" ]

# Dev FrankenPHP image
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev XDEBUG_MODE=off
VOLUME /app/var/

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN set -eux; \
	install-php-extensions \
		xdebug \
	;

COPY --link infrastructure/docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch" ]

# Worker FrankenPHP image
FROM frankenphp_base AS app_workers

VOLUME /app/var/

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY --link infrastructure/docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/
COPY --link --chmod=700 infrastructure/systemd/insert-user.sh /usr/local/bin/insert-user

RUN mkdir -p /run/systemd && \
    echo 'docker' > /run/systemd/container && \
    ln -sf /lib/systemd/systemd /sbin/init

# Copy the systemd service file
COPY infrastructure/systemd/worker.service /etc/systemd/system/worker.service

# Enable the service
RUN systemctl enable worker.service

RUN /usr/local/bin/insert-user

CMD ["/sbin/init", "systemctl start worker.service"]

# Prod FrankenPHP image
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="import worker.Caddyfile"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link infrastructure/docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/
COPY --link infrastructure/docker/php/worker.Caddyfile /etc/caddy/worker.Caddyfile

# prevent the reinstallation of vendors at every changes in the source code
COPY --link composer.* symfony.* ./
RUN set -eux; \
	composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

# copy sources
COPY --link . ./
RUN rm -Rf infrastructure/docker/

RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	composer dump-env prod; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console; sync;