FROM php:8.3-cli-alpine

WORKDIR /app

RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    postgresql-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_pgsql mbstring intl bcmath pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts

COPY . .

RUN composer dump-autoload --optimize --no-interaction

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
