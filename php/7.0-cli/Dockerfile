FROM php:7.0-cli

ENV PHP_MEMORY_LIMIT 2G
ENV MAGENTO_ROOT /app
ENV DEBUG false
ENV MAGENTO_RUN_MODE production
ENV COMPOSER_ALLOW_SUPERUSER 1

# Install dependencies
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
  sendmail-bin \
  sendmail \
  sudo \
  cron \
  rsyslog \
  mysql-client \
  git \
  redis-tools \
  nano \
  unzip \
  vim \
  libbz2-dev \
  libjpeg62-turbo-dev \
  libpng-dev \
  libfreetype6-dev \
  libgeoip-dev \
  wget \
  libgmp-dev \
  libmagickwand-dev \
  libmagickcore-dev \
  libc-client-dev \
  libkrb5-dev \
  libicu-dev \
  libldap2-dev \
  libmcrypt-dev \
  libpspell-dev \
  librecode0 \
  librecode-dev \
  libsodium-dev \
  libssh2-1 \
  libssh2-1-dev \
  libtidy-dev \
  libxslt1-dev \
  libyaml-dev

# Configure the gd library
RUN docker-php-ext-configure \
  gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/
RUN docker-php-ext-configure \
  imap --with-kerberos --with-imap-ssl
RUN docker-php-ext-configure \
  ldap --with-libdir=lib/x86_64-linux-gnu
RUN docker-php-ext-configure \
  opcache --enable-opcache

# Install required PHP extensions
RUN docker-php-ext-install -j$(nproc) \
  bcmath \
  bz2 \
  calendar \
  exif \
  gd \
  gettext \
  gmp \
  imap \
  intl \
  ldap \
  mcrypt \
  mysqli \
  opcache \
  pdo_mysql \
  pspell \
  recode \
  shmop \
  soap \
  sockets \
  sysvmsg \
  sysvsem \
  sysvshm \
  tidy \
  xmlrpc \
  xsl \
  zip \
  pcntl

RUN pecl install -o -f \
  geoip-1.1.1 \
  igbinary \
  imagick \
  mailparse \
  msgpack \
  oauth \
  propro \
  raphf \
  redis \
  libsodium \
  ssh2-1.1.2 \
  xdebug-2.6.1 \
  yaml

RUN docker-php-ext-enable \
  bcmath \
  bz2 \
  calendar \
  exif \
  gd \
  geoip \
  gettext \
  gmp \
  igbinary \
  imagick \
  imap \
  intl \
  ldap \
  mailparse \
  mcrypt \
  msgpack \
  mysqli \
  oauth \
  opcache \
  pdo_mysql \
  propro \
  pspell \
  raphf \
  recode \
  redis \
  shmop \
  soap \
  sockets \
  sodium \
  ssh2 \
  sysvmsg \
  sysvsem \
  sysvshm \
  tidy \
  xdebug \
  xmlrpc \
  xsl \
  yaml \
  zip \
  pcntl

ADD etc/php-cli.ini /usr/local/etc/php/conf.d/zz-magento.ini
ADD etc/php-xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug-settings.ini
ADD etc/mail.ini /usr/local/etc/php/conf.d/zz-mail.ini

VOLUME /root/.composer/cache
# Get composer installed to /usr/local/bin/composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD bin/* /usr/local/bin/

ADD docker-entrypoint.sh /docker-entrypoint.sh
RUN ["chmod", "+x", "/docker-entrypoint.sh"]
ENTRYPOINT ["/docker-entrypoint.sh"]

RUN ["chmod", "+x", "/usr/local/bin/magento-installer"]
RUN ["chmod", "+x", "/usr/local/bin/magento-command"]
RUN ["chmod", "+x", "/usr/local/bin/ece-command"]
RUN ["chmod", "+x", "/usr/local/bin/cloud-build"]
RUN ["chmod", "+x", "/usr/local/bin/cloud-deploy"]
RUN ["chmod", "+x", "/usr/local/bin/run-cron"]

WORKDIR ${MAGENTO_ROOT}

CMD ["bash"]
