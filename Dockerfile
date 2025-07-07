FROM ubuntu:22.04

LABEL maintainer="Taylor Otwell"

ARG WWWGROUP=1000
ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

WORKDIR /var/www/html/digitwhale_pva_backend

# Set timezone
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install dependencies and PHP
RUN apt-get update \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git libcap2-bin libpng-dev python2 dnsutils librsvg2-bin netcat iputils-ping telnet vim default-mysql-client \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y php8.2-cli php8.2-dev \
    php8.2-curl \
    php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-zip php8.2-bcmath \
    php8.2-msgpack php8.2-igbinary\
    php8.2-memcached php8.2-pcov php8.2-xdebug \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && apt-get update \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*


# setcap for binding to low ports
RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.2 

# Copy application files
COPY . .

RUN cp .env.prod .env

# Install dependencies
RUN composer install --no-ansi --no-dev --no-interaction --no-plugins --no-progress --no-scripts --optimize-autoloader

# Change the permission for the storage folder to allow logging
RUN chmod -R 777 storage

# Change the permission for the bootstrap folder to allow caching of configuration
RUN chmod -R 777 bootstrap

EXPOSE 8000

# Default command to serve Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
