FROM php:8.1-apache

COPY . /var/www/html/

# Enable mod_rewrite if using Laravel or frameworks
RUN a2enmod rewrite

# Change Apache document root to /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

RUN docker-php-ext-install mysqli pdo pdo_mysql

EXPOSE 80

CMD ["apache2-foreground"]
