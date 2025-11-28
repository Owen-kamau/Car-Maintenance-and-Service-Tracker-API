FROM php:8.2-apache

# Enable PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy custom php.ini
COPY docker/php.ini /usr/local/etc/php/php.ini

# Enable Apache mod_rewrite (if you ever need routing)
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Copy project files
COPY src/ /var/www/html/
