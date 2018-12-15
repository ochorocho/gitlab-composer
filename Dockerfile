FROM alpine:3.7

# Install PHP + mods
RUN apk --update --no-cache --update-cache --allow-untrusted add \
    git curl php7 php7-json php7-mbstring php7-openssl php7-phar php7-pear php7-dev yaml yaml-dev g++ make autoconf && \
    pecl channel-update pecl.php.net && \
# Install yaml - no prompt
    yes '' | pecl install http://pecl.php.net/get/yaml && \
# Configure php.ini
    echo $'memory_limit = 1024M\nextension=yaml.so' >> /etc/php7/php.ini && \
# Cleanup image
    apk del make git g++ gcc binutils curl && \
    rm -rf /var/cache/apk/* && \
# Link Binary
    ln -s /gitlab-composer/gitlab-composer /usr/bin/gitlab-composer

COPY . /gitlab-composer
WORKDIR /gitlab-composer/