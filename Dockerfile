# Dockerfile pour Laravel + Vite (sans Nginx)
FROM php:8.2-fpm-alpine

# Installer dépendances système avec npm
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    nodejs \
    npm \
    libzip-dev \
    libpng-dev \
    oniguruma-dev \
    postgresql-dev

# Installer extensions PHP
RUN docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    zip \
    exif \
    pcntl \
    gd \
    bcmath

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier uniquement les fichiers nécessaires d'abord (optimisation)
COPY composer.json composer.lock package.json package-lock.json* /var/www/html/
WORKDIR /var/www/html

# Installer dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Installer dépendances Node (vérifie si package-lock.json existe)
RUN if [ -f package-lock.json ]; then npm ci --only=production; else npm install --only=production; fi

# Copier le reste de l'application
COPY . .

# Permissions pour Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Builder Vite (si vite.config.js existe)
RUN if [ -f vite.config.js ] || [ -f vite.config.ts ]; then npm run build; fi

# Nettoyer
RUN rm -rf /root/.npm /tmp/*

# Créer le lien de stockage
RUN php artisan storage:link || true

# Nettoyer le cache
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Exposer port PHP-FPM
EXPOSE 80

# Démarrer PHP-FPM
CMD ["php-fpm"]