FROM php:7-cli

# dependencies
RUN apt-get update -yqq \
    && apt-get install -y \
       git \
       unzip \
       vim \
       locales \
       zlib1g-dev \
    && rm -r /var/lib/apt/lists/*

# Fix locale
RUN locale-gen "en_US.UTF-8" \
    && dpkg-reconfigure locales

# PECL PHP extensions
RUN pecl install mongodb

# PHP extensions
#RUN docker-php-ext-install mbstring

# PHP Settings
RUN echo extension=mongodb.so > /usr/local/etc/php/conf.d/mongodb.ini

# Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && chmod +x /usr/local/bin/composer \
    && composer global require hirak/prestissimo

# Cleanup
RUN apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false -o APT::AutoRemove::SuggestsImportant=false

CMD /bin/bash

