FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl unzip libzip-dev libpng-dev libonig-dev libxml2-dev nodejs npm \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first (for caching)
COPY composer.json composer.lock ./

# Install PHP dependencies WITHOUT running artisan yet
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy frontend dependencies
COPY package.json package-lock.json* ./
RUN if [ -f package.json ]; then npm install; fi

# Copy full application
COPY . .

# Now Laravel exists → safe to run artisan
RUN php artisan package:discover --ansi || true
RUN php artisan config:clear || true
RUN php artisan storage:link || true

# Build frontend if exists
RUN if [ -f package.json ]; then npm run build; fi

# Expose port
EXPOSE 80

# Start Laravel
CMD php artisan serve --host=0.0.0.0 --port=80