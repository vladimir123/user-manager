FROM php:8.3-fpm-alpine

# System dependencies
RUN apk add --no-cache \
    git curl libpng-dev libzip-dev zip unzip \
    postgresql-dev nodejs npm

# PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip gd bcmath pcntl opcache

# Opcache config
RUN echo "opcache.enable=1\nopcache.memory_consumption=128\nopcache.max_accelerated_files=10000\nopcache.revalidate_freq=0" \
    >> /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for caching
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy package files and install npm
COPY package.json package-lock.json ./
RUN npm ci

# Copy rest of source
COPY . .

# Build assets
RUN npm run build

# Composer autoloader
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
