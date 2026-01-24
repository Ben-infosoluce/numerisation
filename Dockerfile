# Dockerfile minimal pour Laravel + Vite + Tailwind
FROM php:8.2-fpm-alpine

# Installer dépendances système
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    nodejs \
    npm \
    libzip-dev \
    libpng-dev \
    oniguruma-dev \
    postgresql-dev \
    # Nginx pour servir les assets Vite
    nginx \
    supervisor

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

# Configurer Nginx pour servir Laravel et assets Vite
RUN mkdir -p /run/nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Configurer Supervisor pour gérer PHP-FPM et Nginx
COPY docker/supervisor.conf /etc/supervisor/conf.d/supervisor.conf

# Copier le projet
WORKDIR /var/www/html
COPY . .

# Permissions pour Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Installer dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Installer dépendances Node et builder Vite
RUN npm ci --only=production \
    && npm run build

# Nettoyer le cache npm pour réduire la taille de l'image
RUN rm -rf /root/.npm /tmp/*

# Créer le lien de stockage (si nécessaire)
RUN php artisan storage:link || true

# Exposer port HTTP (Nginx écoute sur 80)
EXPOSE 80

# Démarrer avec Supervisor (gère PHP-FPM et Nginx)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]