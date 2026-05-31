FROM php:8.4.21-fpm-alpine

# install php extension installer
COPY --from=mlocati/php-extension-installer:2.11.1 /usr/bin/install-php-extensions /usr/local/bin/

#sys dep
RUN apk add --no-cache \
    curl \
    bash \
    git \
    zip \
    unzip \
    ca-certificates \
    nodejs \
    npm

#root for npm
RUN mkdir -p /.npm && chmod -R 777 /.npm

#php dep
RUN install-php-extensions \
    pdo_pgsql \
    bcmath \
    mbstring \
    intl \
    xdebug \
    gd \
    opcache \
    pcntl \
    sockets

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

EXPOSE 9000

CMD ["php-fpm", "-F"]