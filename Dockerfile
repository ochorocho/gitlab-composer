FROM alpine:3.10

COPY ./composer.json /gitlab-composer/composer.json
COPY ./composer.lock /gitlab-composer/composer.lock

# Install PHP + mods
RUN apk --update --no-cache --update-cache --allow-untrusted add \
    git curl php7 php7-json php7-mbstring php7-openssl php7-phar g++ make autoconf && \
    # Install Composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer --version=1.9.0 && \
    # Configure php.ini
    echo $'memory_limit = 1024M' >> /etc/php7/php.ini && \
    rm -rf /var/cache/apk/* && \
    echo "{}" > ~/.composer/composer.json && \
    # Install composer packages
    cd /gitlab-composer && composer install --no-dev && composer clear-cache && \
    # Cleanup image
    apk del make g++ libgcc gcc binutils curl libcurl autoconf perl && \
    rm /usr/bin/composer

ENV PATH="/gitlab-composer/vendor/bin:${PATH}"

WORKDIR /gitlab-composer/
