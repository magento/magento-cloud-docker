{%note%}
FROM php:{%version%}-fpm-stretch

ENV PHP_MEMORY_LIMIT 2G
ENV MAGENTO_ROOT /app
ENV DEBUG false
ENV MAGENTO_RUN_MODE production
ENV UPLOAD_MAX_FILESIZE 64M
ENV UPDATE_UID_GID false
{%env_php_extensions%}

# Install dependencies
RUN apt-get update \
  && apt-get upgrade -y \
  && apt-get install -y --no-install-recommends \
  {%packages%} \
  && rm -rf /var/lib/apt/lists/*

# Configure the gd library
{%docker-php-ext-configure%}

# Install required PHP extensions
{%docker-php-ext-install%}

{%php-pecl-extensions%}

{%installation_scripts%}

{%docker-php-ext-enable%}

RUN groupadd -g 1000 www && useradd -g 1000 -u 1000 -d ${MAGENTO_ROOT} -s /bin/bash www

COPY etc/php-fpm.ini /usr/local/etc/php/conf.d/zz-magento.ini
COPY etc/php-xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug-settings.ini
COPY etc/mail.ini /usr/local/etc/php/conf.d/zz-mail.ini
COPY etc/php-fpm.conf /usr/local/etc/
COPY etc/www.conf /usr/local/etc/php-fpm.d/

COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN ["chmod", "+x", "/docker-entrypoint.sh"]
ENTRYPOINT ["/docker-entrypoint.sh"]

RUN chown -R www:www /usr/local/etc

{%volumes_cmd%}

{%volumes_def%}

USER www:www

WORKDIR ${MAGENTO_ROOT}

CMD ["php-fpm", "-F"]
