# ─────────────────────────────────────────────────────────────────────────────
# Baseline Chat — Production Dockerfile
# PHP 8.2 + Apache + Composer + Node/Vite
# ─────────────────────────────────────────────────────────────────────────────

FROM php:8.2-apache

# ── System dependencies ───────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    libzip-dev zip unzip nodejs npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── PHP extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath gd

# ── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ── Working directory ─────────────────────────────────────────────────────────
WORKDIR /var/www/html

# ── Copy source ───────────────────────────────────────────────────────────────
COPY . .

# ── PHP dependencies (no dev) ─────────────────────────────────────────────────
RUN composer install --no-dev --optimize-autoloader

# ── Frontend assets ───────────────────────────────────────────────────────────
RUN npm install && npm run build

# ── Apache: point DocumentRoot to /public & enable mod_rewrite ───────────────
RUN sed -i 's|/var/www/html|/var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# ── Permissions ───────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ── Port ──────────────────────────────────────────────────────────────────────
EXPOSE 80

# ── Start: migrate → cache config → start Apache ────────────────────────────
CMD ["sh", "-c", "php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && apache2-foreground"]
