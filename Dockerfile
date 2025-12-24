FROM php:8.2-apache

WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpq-dev \
    libicu-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

    # Install PHP extensions required by Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    curl \
    xml \
    soap

# Enable Apache mod_rewrite for Laravel routing
RUN a2enmod rewrite

# Set Apache DocumentRoot to Laravel public folder
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/public|g' /etc/apache2/sites-available/000-default.conf

# Copy app files
COPY . /var/www

# Ensure all required directories and files exist
# RUN mkdir -p /var/www/resources/views/vendor/installer/permissions

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js 20.x and npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install Laravel dependencies
RUN composer install --no-interaction --prefer-dist

# Install Node dependencies and build assets
RUN npm ci --only=production \
    && npm run build \
    && rm -rf node_modules


# Laravel caching and optimization
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache 

    
    # Fix permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/resources \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/resources /var/www/storage/logs /var/www

RUN php artisan storage:link

EXPOSE 80