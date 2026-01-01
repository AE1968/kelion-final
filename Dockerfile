FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install SQLite extension
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Create storage directory with proper permissions
RUN mkdir -p storage && chmod -R 777 storage

# Configure Apache to allow .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set document root permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port (Railway uses PORT env variable)
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
