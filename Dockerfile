# ─────────────────────────────────────────────────────────────────────────────
# Baseline Chat — Production Dockerfile
# PHP 8.2-FPM (Alpine) + Nginx — no Apache MPM conflicts
# ─────────────────────────────────────────────────────────────────────────────

FROM php:8.2-fpm-alpine

# ── System dependencies ───────────────────────────────────────────────────────
RUN apk add --no-cache \
    nginx git curl libpng-dev oniguruma-dev libxml2-dev \
    libzip-dev zip unzip nodejs npm

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

# ── Nginx config ──────────────────────────────────────────────────────────────
RUN mkdir -p /run/nginx
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# ── Permissions ───────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# ── Port ──────────────────────────────────────────────────────────────────────
EXPOSE 80

# ── Start: migrate → cache → php-fpm → nginx ────────────────────────────────
CMD ["sh", "-c", "php artisan migrate --force; php artisan config:cache; php artisan route:cache; php artisan view:cache; php-fpm -D; nginx -g 'daemon off;'"]
