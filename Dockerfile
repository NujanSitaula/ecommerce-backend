FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git curl unzip libzip-dev libpng-dev libonig-dev libxml2-dev nodejs npm \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

COPY package.json package-lock.json* ./
RUN if [ -f package.json ]; then npm install; fi

COPY . .

RUN if [ -f artisan ]; then php artisan config:clear || true; fi
RUN if [ -f package.json ]; then npm run build; fi
RUN php artisan storage:link || true

EXPOSE 80

CMD php artisan serve --host=0.0.0.0 --port=80
