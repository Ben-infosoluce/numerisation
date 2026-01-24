# Dockerfile minimal pour Laravel + Vite + Tailwind
FROM php:8.2-fpm

# Installer dépendances système
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev libpng-dev libonig-dev nodejs npm \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl gd

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier le projet
WORKDIR /var/www/html
COPY . .

# Permissions pour Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Installer dépendances PHP & Node
RUN composer install --no-dev --optimize-autoloader
RUN npm install
RUN npm run build

# Exposer port HTTP
EXPOSE 9000

# Démarrer PHP-FPM
CMD ["php-fpm"]
