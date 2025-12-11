# Use an official PHP runtime as a parent image
FROM php:8.2-apache

# Install system dependencies and PHP extensions required for Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Enable Apache mod_rewrite for Laravel routing
RUN a2enmod rewrite

# Copy the Laravel application files
COPY . /var/www/html/

# Set the working directory
WORKDIR /var/www/html

# Copy the Apache configuration
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install application dependencies (without dev dependencies for production)
RUN composer install --no-dev --optimize-autoloader

# Change ownership of the /var/www/html directory to the www-data user
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Set permissions
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache