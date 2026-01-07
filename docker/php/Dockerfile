# Dockerfile
FROM php:8.2-fpm

# Instala dependências do sistema de forma otimizada
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libicu-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala extensões PHP necessárias para o projeto e PHPMailer/Google API
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl opcache

# Instala o Composer de forma sênior (usando a imagem oficial como fonte)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html

# Ajusta permissões para o usuário padrão do PHP-FPM (www-data)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]