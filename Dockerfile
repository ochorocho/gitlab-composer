FROM alpine:3.10

# Install PHP + mods
RUN apk --update --no-cache --update-cache --allow-untrusted add \
    git curl php7 php7-json php7-mbstring php7-openssl php7-phar g++ make autoconf && \
    # Configure php.ini
    echo $'memory_limit = 1024M' >> /etc/php7/php.ini && \
    # Cleanup image
    apk del make g++ libgcc gcc binutils git curl libcurl autoconf perl && \
    rm -rf /var/cache/apk/* && \
    mkdir ~/.composer/ && \
    echo "{}" > ~/.composer/composer.json

COPY ./vendor /gitlab-composer/vendor
COPY ./composer.json /gitlab-composer/composer.json
COPY ./composer.lock /gitlab-composer/composer.lock

ENV PATH="/gitlab-composer/vendor/bin:${PATH}"

# Install composer packages
RUN cd /gitlab-composer && composer install && composer clear-cache

WORKDIR /gitlab-composer/
