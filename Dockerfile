FROM php:8.3-cli

WORKDIR /app

# System dependencies
RUN apt-get update && apt-get install -y \
    unzip git zip curl libicu-dev libzip-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip

# Install Composer properly
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Ensure permissions (prevents exit 127 / permission issues)
RUN chmod +x /usr/local/bin/composer

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Railway uses PORT env sometimes, but 8080 is fine fallback
EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]