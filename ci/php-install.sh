#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

apt-get update \
&& apt-get -y --no-install-recommends install \
        curl \
        git \
        php8.4-bcmath \
        php8.4-bz2 \
		php8.4-cli \
		php8.4-common \
		php8.4-curl \
        php8.4-gd \
		php8.4-igbinary \
        php8.4-imagick \
        php8.4-intl \
        php8.4-mcrypt \
		php8.4-mbstring \
		php8.4-memcache \
		php8.4-memcached \
		php8.4-oauth \
		php8.4-opcache \
        php8.4-pgsql \
		php8.4-pspell \
		php8.4-readline \
        php8.4-redis \
		php8.4-uuid \
        php8.4-xdebug \
		php8.4-xml \
		php8.4-xmlrpc \
        php8.4-xsl \
        php8.4-yaml \
		php8.4-zip \
        zip \
&& apt-get clean \
&& rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* \
&& curl -sS https://get.symfony.com/cli/installer | bash \
&& mv /root/.symfony5/bin/symfony /usr/local/bin/symfony \
&& curl -sS https://getcomposer.org/installer | php
