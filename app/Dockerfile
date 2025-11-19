FROM php:8.2-apache

# PDO MySQL eklentisini aktif etme
RUN docker-php-ext-install pdo pdo_mysql

# Apache için ayar
RUN a2enmod rewrite

WORKDIR /var/www/html
