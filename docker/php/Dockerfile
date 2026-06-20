FROM php:8.5-fpm

ARG WWWUSER=1000
ARG WWWGROUP=1000

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer-cache \
    HOME=/var/www \
    NPM_CONFIG_CACHE=/tmp/.npm \
    XDG_CONFIG_HOME=/tmp/.config

# Define o diretório de trabalho
WORKDIR /var/www

# Instala as dependências do sistema
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash \
        ca-certificates \
        curl \
        default-mysql-client \
        git \
        gnupg \
        libicu-dev \
        libzip-dev \
        unzip

# Instala Node.js e npm
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs

# Limpa o cache do apt
RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instala as extensões do PHP
RUN docker-php-ext-install \
    bcmath \
    intl \
    pcntl \
    pdo_mysql \
    zip

# Instala Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configura o usuário da aplicação e diretórios graváveis
RUN groupmod -g ${WWWGROUP} www-data \
    && usermod -u ${WWWUSER} -g ${WWWGROUP} www-data \
    && mkdir -p /tmp/.config/psysh /tmp/.npm \
    && chown -R www-data:www-data /tmp/.config /tmp/.npm

# Copia o Composer oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia configurações do PHP e entrypoint
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini
COPY docker/php/entrypoint.sh /usr/local/bin/docker-entrypoint

RUN chmod +x /usr/local/bin/docker-entrypoint

USER www-data

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]
