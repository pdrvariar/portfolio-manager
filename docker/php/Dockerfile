FROM php:8.2-fpm-alpine

RUN apk update && apk add \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_mysql zip bcmath

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN npm install -g npm@latest

WORKDIR /var/www/html

COPY src/ /var/www/html/

RUN composer install --no-interaction --optimize-autoloader

EXPOSE 9000
CMD ["php-fpm"]