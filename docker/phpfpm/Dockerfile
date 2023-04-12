FROM php:8.1-fpm

ENV SYMFONY_DEPRECATIONS_HELPER=disabled
ENV XDEBUG_MODE=off

RUN apt-get update && apt-get install -y libsqlite3-dev libsodium-dev \
    	zlib1g-dev libicu-dev g++ libzip-dev libpq-dev libzip-dev libzstd-dev bash

RUN docker-php-ext-configure mysqli --with-mysqli=mysqlnd \
    && yes | pecl install igbinary apcu xdebug-3.1.6 redis \
    && docker-php-ext-install pdo \
    && docker-php-ext-install intl \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install pgsql \
    && docker-php-ext-install pdo_pgsql \
    && docker-php-ext-install pdo_sqlite \
    && docker-php-ext-install opcache \
    && docker-php-ext-install zip \
    && docker-php-ext-install calendar \
    && docker-php-ext-install sockets \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install sodium \
    && docker-php-ext-enable apcu \
    && docker-php-ext-enable igbinary \
    && docker-php-ext-enable redis \
    && docker-php-ext-enable xdebug

RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.load_comments=1'; \
} >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
&& echo 'memory_limit=512M' >> /usr/local/etc/php/conf.d/docker-php-ext-memory-limit.ini

RUN curl -ksS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

RUN BLACKFIRE_INSTALL_METHOD=raw bash -c "$(curl -L https://installer.blackfire.io/installer.sh)" \
    && blackfire php:install
