FROM php:{%version%}-fpm

ENV PHP_MEMORY_LIMIT 2G
ENV MAGENTO_ROOT /app
ENV DEBUG false
ENV MAGENTO_RUN_MODE production
ENV UPLOAD_MAX_FILESIZE 64M

# Install dependencies
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
  {%packages%}

# Configure the gd library
{%docker-php-ext-configure%}

# Install required PHP extensions
{%docker-php-ext-install%}

{%php-pecl-extensions%}

{%docker-php-ext-enable%}

COPY etc/php-fpm.ini /usr/local/etc/php/conf.d/zz-magento.ini
COPY etc/php-xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug-settings.ini
COPY etc/mail.ini /usr/local/etc/php/conf.d/zz-mail.ini
COPY etc/php-fpm.conf /usr/local/etc/

COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN ["chmod", "+x", "/docker-entrypoint.sh"]
ENTRYPOINT ["/docker-entrypoint.sh"]

CMD ["php-fpm", "-R"]
