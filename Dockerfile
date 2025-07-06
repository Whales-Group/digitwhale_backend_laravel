FROM php:8.2-fpm

# Install required PHP extensions and tools
RUN apt-get update && apt-get install -y \
    libzip-dev unzip zip curl git \
    && docker-php-ext-install pdo pdo_mysql zip

# Copy Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 👇 Set the correct working directory (your real Laravel path)
WORKDIR /home/root/deployments/digitwhale_pva_backend

# Copy your app into the container
COPY . .

# Install dependencies
RUN composer install --no-ansi --no-dev --no-interaction --no-plugins --no-progress --no-scripts --optimize-autoloader

# Set proper permissions for Laravel to work
RUN chown -R www-data:www-data /home/root/deployments/digitwhale_pva_backend \
    && chmod -R 755 /home/root/deployments/digitwhale_pva_backend/storage

# PHP-FPM runs on port 9000
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
