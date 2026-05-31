FROM php:8.3-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    unzip git libicu-dev libzip-dev zip \
    && docker-php-ext-install intl pdo pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]