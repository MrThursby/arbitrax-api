FROM ubuntu:22.04

LABEL maintainer="Taylor Otwell"

ARG WWWGROUP
ARG NODE_VERSION=20

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC
ENV SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Установка необходимых библиотек
RUN apt-get update \
    && apt-get install -y \
       gnupg \
       curl \
       ca-certificates \
       zip \
       unzip \
       git \
       supervisor \
       sqlite3 \
       libcap2-bin \
       libpng-dev \
       python2 \
       dnsutils \
       librsvg2-bin \
       fswatch \
       ffmpeg \
       nano \
       libssl-dev \
       libbrotli-dev \
       pkg-config \
       libcurl4-openssl-dev \
       libtool \
       automake \
       autoconf \
       make \
       g++ \
       && apt-get clean \
       && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Установка PHP 8.3 и необходимых расширений
RUN apt-get update && apt-get install -y \
       software-properties-common \
    && add-apt-repository ppa:ondrej/php \
    && apt-get update && apt-get install -y \
       php8.3-cli php8.3-dev \
       php8.3-pgsql php8.3-sqlite3 php8.3-gd \
       php8.3-curl php8.3-imap php8.3-mysql php8.3-mbstring \
       php8.3-xml php8.3-zip php8.3-bcmath php8.3-soap \
       php8.3-intl php8.3-readline \
       php8.3-ldap php8.3-msgpack php8.3-igbinary \
       php8.3-redis php8.3-memcached php8.3-pcov php8.3-imagick \
       php8.3-xdebug \
       && apt-get clean \
       && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Установка Swoole
RUN curl -sSL https://github.com/swoole/swoole-src/archive/refs/tags/v5.1.4.tar.gz -o swoole.tar.gz \
    && tar -xzf swoole.tar.gz \
    && cd swoole-src-5.1.4 \
    && phpize \
    && ./configure --enable-openssl --enable-sockets --enable-http2 \
    && make \
    && make install \
    && echo "extension=swoole.so" > /etc/php/8.3/cli/conf.d/99-swoole.ini \
    && cd .. \
    && rm -rf swoole-src-5.1.4 swoole.tar.gz

# Установка MongoDB
RUN pecl install mongodb \
    && echo "extension=mongodb.so" > /etc/php/8.3/cli/conf.d/99-mongodb.ini

# Установка Composer
RUN curl -sSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Установка Node.js и npm
RUN curl -fsSL https://deb.nodesource.com/setup_$NODE_VERSION.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && npm install -g pnpm \
    && npm install -g bun

# Установка Yarn
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor -o /etc/apt/trusted.gpg.d/yarn.gpg \
    && echo "deb https://dl.yarnpkg.com/debian/ stable main" > /etc/apt/sources.list.d/yarn.list \
    && apt-get update \
    && apt-get install -y yarn

RUN groupadd --force -g $WWWGROUP sail \
    && useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.3/cli/conf.d/99-sail.ini

RUN chmod +x /usr/local/bin/start-container

EXPOSE 80/tcp

ENTRYPOINT ["start-container"]
