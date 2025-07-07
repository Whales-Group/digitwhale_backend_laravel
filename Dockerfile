FROM ubuntu:22.04

LABEL maintainer="Taylor Otwell"

ARG WWWGROUP=1000
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

WORKDIR /var/www/html/digitwhale_pva_backend

# Set timezone
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Add PHP repo and install required packages
RUN apt-get update && apt-get install -y \
    curl gnupg git unzip zip ca-certificates software-properties-common \
    && curl -fsSL https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c | gpg --dearmor -o /etc/apt/keyrings/php.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/php.list \
    && apt-get update

# Install PHP extensions (minimal required for Laravel + MySQL)
RUN apt-get install -y \
    php8.2 php8.2-cli php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl \
    php8.2-mysql php8.2-zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
    && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/*

# Create user (for Laravel Sail compatibility if needed)
RUN groupadd --gid $WWWGROUP sail && useradd --uid 1337 --gid $WWWGROUP -m sail

# Expose port
EXPOSE 8000

# Copy application source
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set storage & bootstrap permissions
RUN chmod -R 777 storage bootstrap

# Default command to serve Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
