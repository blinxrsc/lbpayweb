# Use the official PHP 8.3 Apache image
FROM php:8.3-apache

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    gnupg \
    && rm -rf /var/lib/apt/lists/*

# 2. install php extensions 
# Added 'pcntl' which is required for Laravel Horizon!
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 3. install redis extension
RUN pecl install redis && docker-php-ext-enable redis

# 2. Install Node.js and NPM (NodeSource)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 4. enable apache mod_rewrite for laravel urls
RUN a2enmod rewrite

# 5. Install Composer globally (MUST happen before 'composer install')
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. set the working directory inside the container
WORKDIR /var/www/html/lbpayweb

# 7. Copy composer files and install dependencies
# We do this before copying the whole app to save build time (Docker Caching)
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-dev --prefer-dist

# 8. copy your apache configuration
COPY ./docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# 9. copy the entire project
COPY . .

# 10. Finish composer setup
RUN composer dump-autoload --optimize --no-scripts

# 11. Set permissions
# We make the web server (www-data) the owner of everything
RUN chown -R www-data:www-data /var/www/html/lbpayweb
RUN chown -R $USER:www-data /var/www/html/lbpayweb/storage /var/www/html/lbpayweb/bootstrap/cache
RUN chmod -R 775 /var/www/html/lbpayweb/storage /var/www/html/lbpayweb/bootstrap/cache

# Expose port 80
EXPOSE 80
