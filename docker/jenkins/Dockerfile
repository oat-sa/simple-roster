FROM php:7.2-fpm

ENV APP_ENV=test
ENV SYMFONY_DEPRECATIONS_HELPER=max[direct]=10

RUN apt-get update && \
    apt-get install -y libpng-dev libjpeg-dev libpq-dev zip unzip sudo wget sqlite3 libsqlite3-dev libsodium-dev zlib1g-dev libicu-dev g++ && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr

RUN yes | pecl install igbinary apcu xdebug

RUN docker-php-ext-install pdo && \
    docker-php-ext-install intl && \
    docker-php-ext-install pdo_sqlite && \
    docker-php-ext-install gd && \
    docker-php-ext-install mbstring && \
    docker-php-ext-install opcache && \
    docker-php-ext-install zip && \
    docker-php-ext-install calendar && \
    docker-php-ext-install sockets && \
    docker-php-ext-install pcntl && \
    docker-php-ext-install sodium && \
    docker-php-ext-enable apcu && \
    docker-php-ext-enable igbinary && \
    docker-php-ext-enable xdebug

RUN { \
    echo 'memory_limit=512M'; \
} >> /usr/local/etc/php/conf.d/docker-php-ext-memory-limit.ini

RUN rm -rf /var/www/html \
    && chmod 0777 /tmp/
