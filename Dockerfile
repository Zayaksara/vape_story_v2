FROM php:8.4-cli

# System dependencies
RUN apt-get update && apt-get install -y \
    git curl unzip ca-certificates \
    libpq-dev libzip-dev libonig-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions (pdo_pgsql for Postgres, gd/mbstring for mpdf, etc.)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip gd bcmath mbstring

# Node 20 (untuk build asset Vue/Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

# .env sementara agar `php artisan` (dipanggil wayfinder & composer scripts) bisa boot saat build.
# Saat runtime, env var dari Render menimpa nilai-nilai ini.
RUN cp .env.example .env

RUN composer install --optimize-autoloader --no-dev --no-interaction

RUN php artisan key:generate --force

# Hapus lockfile agar npm resolve native binary (rollup/lightningcss) sesuai platform Linux.
RUN rm -f package-lock.json pnpm-lock.yaml && npm install

RUN npm run build

# Hapus .env sementara: saat runtime, konfigurasi murni dari env var Render.
RUN rm -f .env

# Render meng-inject $PORT saat runtime
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
