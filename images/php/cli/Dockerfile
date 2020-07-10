{%note%}
FROM php:{%version%}-cli-buster
ARG GOSU_VERSION=1.11

ENV COMPOSER_MEMORY_LIMIT -1
ENV PHP_MEMORY_LIMIT 2G
ENV MAGENTO_ROOT /app
ENV DEBUG false
ENV MAGENTO_RUN_MODE production
ENV UPDATE_UID_GID false
ENV COMPOSER_ALLOW_SUPERUSER 1

{%env_php_extensions%}

# Configure Node.js version
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash

# Install dependencies
RUN apt-get update \
  && apt-get upgrade -y \
  && apt-get install -y --no-install-recommends \
  {%packages%} \
  && rm -rf /var/lib/apt/lists/*

# Install PyYAML
RUN pip3 install --upgrade setuptools \
    && pip3 install pyyaml

# Install Grunt
RUN npm install -g grunt-cli

# Configure the gd library
{%docker-php-ext-configure%}

# Install required PHP extensions
{%docker-php-ext-install%}

{%php-pecl-extensions%}

{%installation_scripts%}

{%docker-php-ext-enable%}

ADD etc/php-cli.ini /usr/local/etc/php/conf.d/zz-magento.ini
ADD etc/php-xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug-settings.ini
ADD etc/php-pcov.ini /usr/local/etc/php/conf.d/zz-pcov-settings.ini
ADD etc/mail.ini /usr/local/etc/php/conf.d/zz-mail.ini
ADD etc/php-gnupg.ini /usr/local/etc/php/conf.d/gnupg.ini

# Get composer installed to /usr/local/bin/composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD bin/* /usr/local/bin/

RUN groupadd -g 1000 www && useradd -g 1000 -u 1000 -d ${MAGENTO_ROOT} -s /bin/bash www

ADD docker-entrypoint.sh /docker-entrypoint.sh

RUN ["chmod", "+x", \
    "/docker-entrypoint.sh", \
    "/usr/local/bin/magento-installer", \
    "/usr/local/bin/magento-command", \
    "/usr/local/bin/mftf-command", \
    "/usr/local/bin/ece-command", \
    "/usr/local/bin/cloud-build", \
    "/usr/local/bin/cloud-deploy", \
    "/usr/local/bin/cloud-post-deploy", \
    "/usr/local/bin/run-cron", \
    "/usr/local/bin/run-hooks" \
]

{%volumes_cmd%}

{%volumes_def%}

ENTRYPOINT ["/docker-entrypoint.sh"]

USER root

WORKDIR ${MAGENTO_ROOT}

CMD ["bash"]
