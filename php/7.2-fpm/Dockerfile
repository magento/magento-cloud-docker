FROM php:7.2-fpm

ENV PHP_MEMORY_LIMIT 2G
ENV PHP_ENABLE_XDEBUG false
ENV MAGENTO_ROOT /app
ENV DEBUG false
ENV MAGENTO_RUN_MODE production
ENV UPLOAD_MAX_FILESIZE 64M

# Install dependencies
RUN apt-get update \
  && apt-get install -y \
  sendmail-bin \
  sendmail \
  sudo \
  libjpeg62-turbo-dev \
  libpng-dev \
  libfreetype6-dev \
  libicu-dev \
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

COPY etc/php-fpm.ini /usr/local/etc/php/conf.d/zz-magento.ini
COPY etc/php-xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug-settings.ini
COPY etc/mail.ini /usr/local/etc/php/conf.d/zz-mail.ini
COPY etc/php-fpm.conf /usr/local/etc/

COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN ["chmod", "+x", "/docker-entrypoint.sh"]
ENTRYPOINT ["/docker-entrypoint.sh"]

CMD ["php-fpm", "-R"]
