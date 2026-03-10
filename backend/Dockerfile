FROM php:8.2-apache

# Instalar extensiones del sistema
RUN apt-get update && apt-get install -y \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-webp --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql sockets gd \
    && rm -rf /var/lib/apt/lists/*