FROM php:7.0-cli

ENV PHP_MEMORY_LIMIT 2G
ENV PHP_ENABLE_XDEBUG false
ENV MAGENTO_ROOT /app
ENV DEBUG false
ENV MAGENTO_RUN_MODE production
ENV COMPOSER_ALLOW_SUPERUSER 1

# Install dependencies
RUN apt-get update \
  && apt-get install -y \
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
  libjpeg62-turbo-dev \
  libpng-dev \
  libfreetype6-dev \
  libicu-dev \
  libmcrypt-dev \
  libssh2-1 \
  libssh2-1-dev \
  libxslt1-dev

# Configure the gd library
RUN docker-php-ext-configure \
  gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/

# Install required PHP extensions
RUN docker-php-ext-install -j$(nproc) \
  bcmath \
  gd \
  intl \
  mcrypt \
  opcache \
  pdo_mysql \
  soap \
  sockets \
  xsl \
  zip \
  pcntl

RUN pecl install -o -f \
  ssh2-1.1.2 \
  xdebug

RUN docker-php-ext-enable \
  bcmath \
  gd \
  intl \
  mcrypt \
  opcache \
  pdo_mysql \
  soap \
  sockets \
  ssh2 \
  xdebug \
  xsl \
  zip \
  pcntl

RUN rm -f /usr/local/etc/php/conf.d/*.ini

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
