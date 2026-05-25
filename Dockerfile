FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk update && apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    autoconf \
    build-base

# Configure & Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql gd zip intl bcmath opcache mbstring xml

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/web/be_CMS

# Copy source code
COPY . .

# Adjust permissions for Yii writeable directories
RUN mkdir -p runtime web/assets web/uploads \
    && chmod -R 777 runtime web/assets web/uploads

# Install dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Expose port 9000
EXPOSE 9000

CMD ["php-fpm"]
