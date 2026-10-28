# =========================================================================
# Stage 1: Build OLT Telemetry Collector (Golang)
# =========================================================================
FROM golang:1.24-alpine AS go-builder
WORKDIR /build
COPY services/olt-collector/go.mod ./
COPY services/olt-collector/main.go ./
RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -ldflags="-s -w" -o olt-collector .

# =========================================================================
# Stage 2: Production PHP 8.2 + Nginx + Laravel Container
# =========================================================================
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    bash \
    git \
    unzip \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
        intl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/ims2

# Copy source code
COPY . /var/www/ims2

# Copy compiled OLT collector binary from Stage 1
COPY --from=go-builder /build/olt-collector /var/www/ims2/services/olt-collector/olt-collector

# Install composer production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh /var/www/ims2/services/olt-collector/olt-collector \
    && chown -R www-data:www-data /var/www/ims2/storage /var/www/ims2/bootstrap/cache

EXPOSE 8080 8088

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
