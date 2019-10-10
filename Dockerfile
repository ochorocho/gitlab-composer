FROM alpine:3.10

# Install PHP + mods
RUN apk --update --no-cache --update-cache --allow-untrusted add \
    git curl php7 php7-json php7-mbstring php7-openssl php7-phar g++ make autoconf && \
    # Install Composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer && \
    # Configure php.ini
    echo $'memory_limit = 1024M' >> /etc/php7/php.ini && \
    # Cleanup image
    apk del make g++ gcc binutils curl autoconf && \
    rm -rf /var/cache/apk/* && \
    echo "{}" > ~/.composer/composer.json

ENV PATH="/gitlab-composer/vendor/bin:${PATH}"

COPY . /gitlab-composer
COPY config.json /root/.composer/config.json


# Install composer packages
RUN cd /gitlab-composer && composer install

WORKDIR /gitlab-composer/
