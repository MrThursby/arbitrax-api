FROM laravelphp/sail:latest

RUN pecl install mongodb \
    && docker-php-ext-enable mongodb