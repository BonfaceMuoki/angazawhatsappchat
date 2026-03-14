FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    curl \
 && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
 && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy existing application (if any)
COPY . /var/www/html

# Install PHP dependencies if composer.json exists
RUN if [ -f "composer.json" ]; then composer install --no-interaction --prefer-dist --optimize-autoloader; fi

# Expose Laravel dev server port
EXPOSE 8000

# Default command: run Laravel dev server
CMD if [ ! -f ".env" ] && [ -f ".env.example" ]; then cp .env.example .env; fi && \
    php artisan key:generate --force && \
    php artisan migrate --force || true && \
    php artisan serve --host=0.0.0.0 --port=8000

