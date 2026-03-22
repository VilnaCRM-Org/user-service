#!/bin/sh
set -e

# Create required directories (needed when host volume is mounted)
mkdir -p var/cache var/log

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	if [ "$APP_ENV" != 'prod' ]; then
		composer install --prefer-dist --no-progress --no-interaction --ignore-platform-reqs --no-scripts
	else
	  composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs --no-scripts
	fi

	# Check MongoDB connection (used for all persistence including OAuth2)
	if [ -n "${MONGODB_URL:-}" ] || grep -q '^MONGODB_URL=' .env 2>/dev/null; then
		echo "Waiting for MongoDB to be ready..."
		ATTEMPTS_LEFT_TO_REACH_MONGO=60
		until [ $ATTEMPTS_LEFT_TO_REACH_MONGO -eq 0 ]; do
			if php bin/console doctrine:mongodb:mapping:info > /dev/null 2>&1; then
				break
			fi
			sleep 1
			ATTEMPTS_LEFT_TO_REACH_MONGO=$((ATTEMPTS_LEFT_TO_REACH_MONGO - 1))
			echo "Still waiting for MongoDB to be ready... Or maybe MongoDB is not reachable. $ATTEMPTS_LEFT_TO_REACH_MONGO attempts left."
		done

		if [ $ATTEMPTS_LEFT_TO_REACH_MONGO -eq 0 ]; then
			echo "MongoDB is not up or not reachable"
			exit 1
		else
			echo "MongoDB is now ready and reachable"
		fi
	fi

	# Run composer auto-scripts after database connections are verified
	composer run-script auto-scripts --no-interaction
	php bin/console lexik:jwt:generate-keypair --skip-if-exists

fi
exec docker-php-entrypoint "$@"
