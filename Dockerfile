FROM php:8.2-apache

# Instalar extensões PHP e dependências
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    tzdata \
    curl \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer 2
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

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

# Permissões corretas
RUN chown -R www-data:www-data storage uploads logs \
    && chmod -R 775 storage uploads logs

EXPOSE 80

CMD ["apache2-foreground"]
