{%note%}
FROM php:{%version%}-fpm-buster
ARG GOSU_VERSION=1.11

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
COPY etc/php-pcov.ini /usr/local/etc/php/conf.d/zz-pcov-settings.ini
COPY etc/mail.ini /usr/local/etc/php/conf.d/zz-mail.ini
COPY etc/php-fpm.conf /usr/local/etc/
COPY etc/php-gnupg.ini /usr/local/etc/php/conf.d/gnupg.ini

COPY fpm-healthcheck.sh /usr/local/bin/fpm-healthcheck.sh
RUN ["chmod", "+x", "/usr/local/bin/fpm-healthcheck.sh"]

HEALTHCHECK --retries=3 CMD ["bash", "/usr/local/bin/fpm-healthcheck.sh"]

COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN ["chmod", "+x", "/docker-entrypoint.sh"]

{%volumes_cmd%}

{%volumes_def%}

ENTRYPOINT ["/docker-entrypoint.sh"]

USER root

WORKDIR ${MAGENTO_ROOT}

CMD ["php-fpm", "-R"]
