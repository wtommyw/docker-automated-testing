FROM php:7.3-apache

RUN apt-get update -y && \
    apt-get install -y openssl zip unzip git && \
    a2enmod rewrite &&  \
    apt-get install -y gnupg

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD . /var/www/html
RUN chown -R www-data:www-data /var/www

WORKDIR /var/www/html
RUN composer install

ENTRYPOINT ["/bin/bash", "-c", "echo 10.150.10.200  42f.test >> /etc/hosts && exec apache2-foreground"]