# Utiliser PHP 8.2 avec extensions nécessaires
FROM php:8.2-fpm

# Installer dépendances système et PHP
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev zip curl npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installer composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le dossier de l'application
WORKDIR /var/www/html
COPY . .

# Installer dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Installer et builder les assets (Vite)
RUN npm install && npm run build

# Exposer le port PHP-FPM (Traefik/Nginx communique via ce port)
EXPOSE 9000

# Commande par défaut : PHP-FPM
CMD ["php-fpm"]
