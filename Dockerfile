FROM php:8.2-cli

RUN apt update \
    && apt-get install -y \
    librdkafka-dev git zip unzip \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN pecl install rdkafka openswoole \
    && docker-php-ext-enable rdkafka openswoole

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app