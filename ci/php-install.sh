#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

apt-get update \
&& apt-get -y --no-install-recommends install \
        curl \
        git \
        php8.3-bcmath \
        php8.3-bz2 \
		php8.3-cli \
		php8.3-common \
		php8.3-curl \
        php8.3-gd \
		php8.3-igbinary \
        php8.3-imagick \
        php8.3-intl \
        php8.3-mcrypt \
		php8.3-mbstring \
		php8.3-memcache \
		php8.3-memcached \
		php8.3-oauth \
		php8.3-opcache \
        php8.3-pgsql \
		php8.3-pspell \
		php8.3-readline \
        php8.3-redis \
		php8.3-uuid \
        php8.3-xdebug \
		php8.3-xml \
		php8.3-xmlrpc \
        php8.3-xsl \
        php8.3-yaml \
		php8.3-zip \
        zip \
&& apt-get clean \
&& rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* \
&& curl -sS https://get.symfony.com/cli/installer | bash \
&& mv /root/.symfony5/bin/symfony /usr/local/bin/symfony \
&& curl -sS https://getcomposer.org/installer | php
