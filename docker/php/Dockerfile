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

# Instala extensões PHP necessárias para o projeto, incluindo as que o Doctrine precisa
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl opcache

# Ativa a extensão opcache
RUN docker-php-ext-enable opcache

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia APENAS os arquivos de dependência primeiro para aproveitar o cache do Docker
COPY composer.json composer.lock ./

# Ajusta permissões temporariamente para instalação
RUN chown -R www-data:www-data /var/www/html

# Primeiro tenta instalar normalmente
RUN composer install --no-interaction --no-scripts --prefer-dist --optimize-autoloader || \
    # Se falhar, atualiza as dependências e tenta novamente
    (echo "Ajustando dependências..." && \
     composer update --no-interaction --no-scripts --prefer-dist && \
     composer install --no-interaction --no-scripts --prefer-dist --optimize-autoloader)

# Copia o restante do código
COPY . .

# Roda os scripts de pós-instalação apenas se necessário
RUN composer run-script post-install-cmd --no-interaction || true

EXPOSE 9000

CMD ["php-fpm"]