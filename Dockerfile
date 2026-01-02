FROM php:8.2-cli

# Install dependencies (SQLite3, extensions, unzip for Composer if needed)
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Ensure storage directory exists and has permissions
RUN mkdir -p storage && chmod -R 777 storage

# Handle 404s by routing everything to known files, or use a router script
# For PHP built-in server, if a file exists it is served. If not, we can route to index.php
# But first, let's keep it simple.

# Expose port 8080 (internal container port)
EXPOSE 8080

# Start PHP server
# -S 0.0.0.0:8080 : Listen on all interfaces, port 8080
# -t /var/www/html : Document root
# index.php : Router script (if using one, otherwise requests simply hit files)
CMD php -S 0.0.0.0:8080 -t /var/www/html
