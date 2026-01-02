FROM php:8.2-fpm-alpine

# Instalar dependências do sistema
RUN apk update && apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    mariadb-client

# Instalar extensões do PHP
RUN docker-php-ext-install pdo pdo_mysql gd soap zip bcmath

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar usuário para aplicação
RUN adduser -D -g 'www' www-user && \
    mkdir -p /var/www/html && \
    chown -R www-user:www-user /var/www/html

# Definir diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos do projeto
# CORREÇÃO: Copiar toda a raiz do projeto, não apenas src/
COPY . /var/www/html/

# Mudar para usuário www-user
USER www-user

# Instalar dependências
RUN composer install --no-interaction --optimize-autoloader