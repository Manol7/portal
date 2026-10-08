FROM php:8.4-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev \
    && docker-php-ext-install intl pdo_mysql opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_HOME=/tmp/composer
WORKDIR /var/www/html
