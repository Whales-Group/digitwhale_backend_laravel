FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive
ENV PHP_VERSION=8.2

# Install PHP + minimal required extensions
RUN apt-get update && apt-get install -y \
    software-properties-common curl gnupg unzip git ca-certificates libzip-dev libpng-dev libonig-dev libxml2-dev libssl-dev pkg-config libicu-dev build-essential autoconf \
    && add-apt-repository ppa:ondrej/php -y \
    && apt-get update && apt-get install -y \
    php${PHP_VERSION}-cli php${PHP_VERSION}-common php${PHP_VERSION}-dev \
    php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-mysql php${PHP_VERSION}-curl php${PHP_VERSION}-zip \
    php${PHP_VERSION}-intl php${PHP_VERSION}-readline php${PHP_VERSION}-swoole \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Allow PHP to bind to ports < 1024
RUN setcap "cap_net_bind_service=+ep" /usr/bin/php${PHP_VERSION}

# Set working directory
WORKDIR /var/www

# Copy application code
COPY . .

# Copy env file
RUN cp /var/www/.env.prod /var/www/.env

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose Laravel Octane port
EXPOSE 8000

# Run Octane server
CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000"]
