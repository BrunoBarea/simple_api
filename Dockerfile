# syntax=docker/dockerfile:1
FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1

# Install system dependencies and PHP extensions required by Symfony and Doctrine
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" intl opcache pdo_pgsql \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure Apache to serve the Symfony public/ directory
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/default-ssl.conf \
    && sed -ri -e "s!Directory /var/www/!Directory ${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy Composer files and install dependencies
COPY composer.json ./
RUN composer install --no-interaction --prefer-dist --no-progress --optimize-autoloader

# Copy the remaining application files
COPY . .

# Prepare writable directories for Symfony cache and logs
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var public

EXPOSE 80

CMD ["apache2-foreground"]
