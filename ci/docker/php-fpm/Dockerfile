FROM php:7.2-fpm

# Copy phpfpm config
COPY docker/php-fpm/app.ini /usr/local/etc/php/conf.d/
# Copy the app_test.php.dist to web/app.php
COPY index.php ../public/index.php

# Yank the node and npm binaries from the official Node docker container
COPY --from=node:14 /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node:14 /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
RUN npm install -g npx

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    zip \
    chromium \
    libpng-dev \
    libgmp-dev \
    libsodium-dev\
    && docker-php-ext-install pdo_mysql exif gd gmp sodium

# Xdebug
RUN pecl install xdebug-3.1.6 && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini;

# Composer
RUN curl -sL https://getcomposer.org/installer | php -- --install-dir /usr/bin --filename composer
RUN mkdir /.composer
RUN chmod -R a+w /.composer

# Npm (this is a temporarily solution and should be fixed '--force --unsafe-perm')
RUN npm cache clean --force --unsafe-perm
RUN mkdir /.npm
RUN chmod -R a+w /.npm

# Install Less.js
RUN npm i -g yarn less

# Cleanup
RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /var/www
