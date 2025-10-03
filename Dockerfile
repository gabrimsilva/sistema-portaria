FROM php:8.2-apache

# Instalar extensões PHP e dependências (COMPLETO - matching Replit)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libc-client-dev \
    libkrb5-dev \
    zip \
    unzip \
    git \
    tzdata \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        intl \
        soap \
        imap \
        mbstring \
        opcache \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar PHP
RUN { \
    echo 'upload_max_filesize = 10M'; \
    echo 'post_max_size = 10M'; \
    echo 'memory_limit = 256M'; \
    echo 'max_execution_time = 300'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'session.cookie_secure = 0'; \
    echo 'session.use_strict_mode = 1'; \
    echo 'opcache.enable = 1'; \
    echo 'opcache.memory_consumption = 128'; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Variáveis de ambiente
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV TZ=America/Sao_Paulo

# Configurar Apache
RUN a2enmod rewrite headers
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Copiar aplicação
WORKDIR /var/www/html
COPY . /var/www/html

# Instalar dependências PHP (otimizado para produção)
RUN composer install --no-dev --classmap-authoritative --optimize-autoloader

# Script de inicialização para permissões
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
