FROM php:8.4.4-fpm-bookworm

RUN apt-get update && apt-get install -y \
        libzip-dev \
    && docker-php-ext-install \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ARG DEV_USER_UID
ARG DEV_USER_GID

RUN groupadd -g ${DEV_USER_GID} hostgroup && \
    useradd -u ${DEV_USER_UID} -g hostgroup -G www-data -m -d /home/dev -s /bin/bash dev && \
    usermod -aG ${DEV_USER_GID} www-data

