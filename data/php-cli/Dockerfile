FROM php:{%version%}-cli

ENV PHP_MEMORY_LIMIT 2G
ENV MAGENTO_ROOT /app
ENV DEBUG false
ENV MAGENTO_RUN_MODE production
ENV COMPOSER_ALLOW_SUPERUSER 1

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
