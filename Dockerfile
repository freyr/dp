FROM php:8.0-alpine3.14 as php-base
WORKDIR /build
RUN apk add --update-cache \
    unzip \
    libzip-dev \
    pcre-dev ${PHPIZE_DEPS} \
    && pecl config-set php_ini ${PHP_INI_DIR}/php.ini \
    && pecl install -f -o \
    redis \
    xdebug \
    && docker-php-ext-install \
    zip \
    && docker-php-ext-enable \
    redis \
    xdebug \
    && rm /usr/local/bin/php-cgi  \
    && rm /usr/local/bin/phpdbg  \
    && rm -rf /tmp/pear ~/.pearrc

# COMPOSER
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/bin --filename=composer

# PHP-BUILD
FROM php-base as php-build
COPY composer.lock composer.json ./
RUN composer install --prefer-dist --no-suggest --no-cache --no-autoloader

COPY --chown=www-data:www-data . .

RUN composer dump-autoload -o --apcu

# PHP-RUNTIME
FROM php-base as php-runtime
WORKDIR /app
RUN chown -R www-data:www-data /app
ENV CHROOT_WWW_DIR=/app

# PHP-FPM
COPY .docker/conf/php.ini $PHP_INI_DIR/php.ini

USER www-data

COPY --chown=www-data:www-data --from=php-build /build .

CMD ["vendor/bin/phpunit"]
