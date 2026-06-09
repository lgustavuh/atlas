# PHP 8.3 FPM como base
FROM php:8.3-fpm-alpine

# Argumentos para UID/GID do usuário (evita problemas de permissão em Linux)
ARG UID=1000
ARG GID=1000

# Variáveis de ambiente
ENV COMPOSER_ALLOW_SUPERUSER=0
ENV COMPOSER_MEMORY_LIMIT=-1

# Instalar dependências do sistema
RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    nodejs \
    npm \
    shadow \
    su-exec \
    $PHPIZE_DEPS

# Configurar e instalar extensões PHP
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        intl \
        gd \
        zip \
        bcmath \
        exif \
        opcache

# Redis para o PHP
RUN pecl install redis \
    && docker-php-ext-enable redis

# Limpeza
RUN apk del $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

# Composer (gerenciador de pacotes PHP)
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Criar usuário não-root para rodar a aplicação
# Importante: nunca rodar PHP-FPM como root em produção
RUN groupmod -g ${GID} www-data \
    && usermod -u ${UID} -g ${GID} www-data \
    && mkdir -p /var/www \
    && chown -R www-data:www-data /var/www

WORKDIR /var/www

# Trocar para usuário não-privilegiado
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
