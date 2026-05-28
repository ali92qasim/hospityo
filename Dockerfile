#Parent Image
FROM php:8.4-fpm

#Set working directory
WORKDIR /var/www

#install system requirements
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_mysql zip

#install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

#copy project files
COPY . .

# Install dependencies only if vendor not present (safe)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader || true

#set permission for laravel
RUN chmod -R 775 storage bootstrap/cache

#expose port
EXPOSE 9000

#start PHP
CMD ["php-fpm"]


