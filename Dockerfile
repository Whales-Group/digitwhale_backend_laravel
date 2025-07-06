FROM php:8.2-apache

RUN a2enmod rewrite
RUN apt-get update && apt-get install -y \
    libzip-dev unzip zip curl git \
    && docker-php-ext-install pdo pdo_mysql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-ansi --no-dev --no-interaction --no-plugins --no-progress --no-scripts --optimize-autoloader
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Overwrite the default Apache config
COPY apache/000-default.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80