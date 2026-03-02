#!/usr/bin/env sh

cd /var/www/html
openssl genpkey -aes-256-cbc -algorithm RSA -pass pass:devpassphrase -out config/secrets/docker/jwt_private.pem
openssl pkey -in config/secrets/docker/jwt_private.pem -passin pass:devpassphrase -out config/secrets/docker/jwt_public.pem -pubout
composer install
