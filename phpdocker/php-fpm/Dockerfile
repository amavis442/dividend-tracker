FROM phpdockerio/php74-fpm:latest
WORKDIR "/application"

# Fix debconf warnings upon build
ARG DEBIAN_FRONTEND=noninteractive

# Install selected extensions and other stuff
RUN apt-get update \
    && apt-get -y --no-install-recommends install  php7.4-memcached php7.4-mysql php7.4-redis php7.4-sqlite3 php7.4-xdebug php7.4-bcmath php7.4-bz2 php7.4-gd php7.4-gearman php7.4-gmp php7.4-igbinary php7.4-imagick php7.4-imap php7.4-intl php7.4-ldap php7.4-pspell php7.4-soap php7.4-xsl php7.4-yaml \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Install git
RUN apt-get update \
    && apt-get -y install git \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*