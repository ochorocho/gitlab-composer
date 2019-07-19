FROM alpine:3.10

# Install PHP + mods
RUN apk --update --no-cache --update-cache --allow-untrusted add \
    git curl php7 php7-json php7-mbstring php7-openssl php7-phar php7-pear php7-dev yaml yaml-dev php7-pecl-yaml g++ make autoconf && \
#    pecl channel-update pecl.php.net && \
# Install yaml - no prompt
#    yes '' | pecl install http://pecl.php.net/get/yaml && \
# Install Composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer && \
# Configure php.ini
    echo $'memory_limit = 1024M\nextension=yaml.so' >> /etc/php7/php.ini && \
# Cleanup image
    apk del make g++ gcc binutils && \
    rm -rf /var/cache/apk/* && \
# Link Binary
    export PATH=$PATH:/gitlab-composer/vendor/bin && \
    echo "{}" > ~/.composer/composer.json

COPY . /gitlab-composer

# Install composer packages
RUN cd /gitlab-composer && composer install

WORKDIR /gitlab-composer/
