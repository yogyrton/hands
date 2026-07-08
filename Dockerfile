FROM webdevops/php-nginx:8.4-alpine

ENV WEB_DOCUMENT_ROOT=/app/public \
    WEB_DOCUMENT_INDEX=index.php \
    SERVICE_NGINX_CLIENT_MAX_BODY_SIZE=100M \
    PHP_POST_MAX_SIZE=100M \
    PHP_UPLOAD_MAX_FILESIZE=100M

USER root

RUN apk add --no-cache bash git curl unzip \
    $PHPIZE_DEPS \
    postgresql-dev

# Composer
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

# PHP extensions для PostgreSQL
RUN docker-php-ext-install pdo_pgsql pgsql

# Redis уже есть в образе, просто включаем
RUN docker-php-ext-enable redis

# Подготовка каталогов
RUN mkdir -p storage bootstrap/cache && chmod -R 777 storage bootstrap/cache
