FROM php:7.4-fpm-alpine

ENV SYMFONY_DEPRECATIONS_HELPER=disabled
ENV XDEBUG_MODE=off

RUN apk add sqlite-dev libsodium-dev \
    	zlib-dev icu-dev g++ libzip-dev postgresql-dev zstd-dev bash

RUN docker-php-ext-configure mysqli --with-mysqli=mysqlnd \
    && yes | pecl install igbinary apcu xdebug redis \
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

RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/alpine/amd64/$version \
    && mkdir -p /tmp/blackfire \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
    && mv /tmp/blackfire/blackfire-*.so $(php -r "echo ini_get('extension_dir');")/blackfire.so \
    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://simple-roster-blackfire:8707\n" > $PHP_INI_DIR/conf.d/blackfire.ini \
    && rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz \
    && rm -rf /var/www/html \
    && chmod 0777 /tmp/ \
    && apk del g++ \
    && rm -rf /tmp/* \
    && rm -rf /var/cache/apk/*
