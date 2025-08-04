# To use this, first build the image:
# docker build . -t php-with-composer
# Then go inside the container:
# docker run -it --rm --name my-php-container php-with-composer
# Or just execute a command:
# docker run -it --rm --name my-running-script php-with-composer composer phpunit
FROM php:8.3.10-fpm

# Install system dependencies and clean cache
RUN apt-get update && apt-get install -y \
 git \
 curl \
 libpng-dev \
 libonig-dev \
 libxml2-dev \
 libcurl4-openssl-dev libssl-dev \ 
 unzip

# Install PHP extensions
RUN docker-php-ext-install mbstring exif pcntl bcmath gd

# Copy Composer from the official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install some development utils
RUN /usr/bin/composer require --dev phpstan/phpstan

COPY . /usr/src/unleash-client-php

WORKDIR /usr/src/unleash-client-php

RUN rm -f composer.lock

RUN composer install

CMD ["sh"]