FROM php:apache

RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install mysqli
RUN a2enmod rewrite

ADD . /var/www
ADD ./public /var/www/html
