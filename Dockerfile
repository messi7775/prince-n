FROM php:8.2-apache

# PHP extension required for MySQL/MariaDB (PDO)
RUN docker-php-ext-install pdo_mysql

# Apache mod_rewrite for .htaccess front-controller routing
RUN a2enmod rewrite

# Allow .htaccess overrides in the document root
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# curl for the compose healthcheck
RUN apt-get update && apt-get install -y --no-install-recommends curl && rm -rf /var/lib/apt/lists/*
