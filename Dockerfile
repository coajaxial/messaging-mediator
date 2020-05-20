FROM alpine:3.11

ADD https://getcomposer.org/download/1.10.5/composer.phar /usr/local/bin/composer

RUN apk add --update --no-cache \
    php7-cli \
    php7-phar \
    php7-iconv \
    php7-json \
    php7-mbstring \
    php7-dom \
    php7-xml \
    php7-xmlwriter \
    php7-openssl \
    php7-tokenizer \
    php7-xdebug \
    php7-ctype \
    php7-soap \
    php7-pcntl \
    php7-posix \
    php7-simplexml \
    git \
    openssh \
    && addgroup -g 1000 www \
    && adduser -D -u 1000 -G www www \
    && install -d -o www -g www /opt/project \
    && ln -s /usr/bin/php7 /usr/local/bin/php \
    && chmod 0755 /usr/local/bin/composer \
    && echo "zend_extension=xdebug.so" > /etc/php7/conf.d/xdebug.ini \
    && :

WORKDIR /opt/project
USER www:www