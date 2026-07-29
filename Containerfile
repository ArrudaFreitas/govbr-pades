FROM docker.io/library/php:7.4-cli

RUN apt-get update \
 && apt-get install -y --no-install-recommends git unzip $PHPIZE_DEPS \
 && pecl install pcov \
 && docker-php-ext-enable pcov \
 && apt-get purge -y --auto-remove $PHPIZE_DEPS \
 && rm -rf /var/lib/apt/lists/*

COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/local/bin/composer
