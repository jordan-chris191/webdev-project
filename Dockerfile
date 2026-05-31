FROM php:8.3-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    unzip git zip curl libicu-dev libzip-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer

# IMPORTANT FIX: allow Symfony Flex + plugins in Docker
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_DISABLE_XDEBUG_WARN=1

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]