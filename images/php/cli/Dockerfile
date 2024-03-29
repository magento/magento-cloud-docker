{%note%}
FROM golang:1.15 AS builder

RUN if [ $(uname -m) = "x86_64" ]; then mailhog_arch="amd64"; else mailhog_arch="arm64"; fi \
    && wget -O mhsendmail.tar.gz https://github.com/mailhog/mhsendmail/archive/refs/tags/v0.2.0.tar.gz \
    && tar -xf mhsendmail.tar.gz \
    && mkdir -p ./src/github.com/mailhog/ \
    && mv ./mhsendmail-0.2.0 ./src/github.com/mailhog/mhsendmail \
    && cd ./src/github.com/mailhog/mhsendmail/ \
    && go get . \
    && GOOS=linux GOARCH=${mailhog_arch} go build -o mhsendmail .

FROM php:{%version%}-cli

ARG COMPOSER_VERSION={%composer_version%}
ARG MAGENTO_ROOT=/app
ARG COMPOSER_ALLOW_SUPERUSER=1
ARG COMPOSER_HOME=/composer
ARG CRONTAB=""

ENV COMPOSER_MEMORY_LIMIT -1
ENV COMPOSER_ALLOW_SUPERUSER ${COMPOSER_ALLOW_SUPERUSER}
ENV COMPOSER_HOME ${COMPOSER_HOME}
ENV COMPOSER_CLEAR_CACHE false
ENV PHP_MEMORY_LIMIT -1
ENV PHP_VALIDATE_TIMESTAMPS 1
ENV DEBUG false
ENV MAGENTO_RUN_MODE production
ENV SENDMAIL_PATH /dev/null
ENV PHPRC ${MAGENTO_ROOT}/php.ini

{%env_php_extensions%}

# Configure Node.js version
RUN curl -sL https://deb.nodesource.com/setup_lts.x | bash

# Install dependencies
RUN apt-get update \
  && apt-get upgrade -y \
  && apt-get install -y --no-install-recommends \
  {%packages%} \
  && rm -rf /var/lib/apt/lists/*

# Install Python packages
{%python_packages%}

# Install Grunt
RUN npm install -g grunt-cli

# Install MailHog
COPY --from=builder /go/src/github.com/mailhog/mhsendmail/mhsendmail /usr/local/bin/
RUN sudo chmod +x /usr/local/bin/mhsendmail

# Configure the gd library
{%docker-php-ext-configure%}

# Install required PHP extensions
{%docker-php-ext-install%}

{%php-pecl-extensions%}

{%installation_scripts%}

ADD etc/php-cli.ini /usr/local/etc/php/conf.d/zz-magento.ini
ADD etc/php-xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug-settings.ini
ADD etc/php-pcov.ini /usr/local/etc/php/conf.d/zz-pcov-settings.ini
ADD etc/mail.ini /usr/local/etc/php/conf.d/zz-mail.ini
ADD etc/php-gnupg.ini /usr/local/etc/php/conf.d/gnupg.ini

# Get composer installed to /usr/local/bin/composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --version=${COMPOSER_VERSION} --filename=composer

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

RUN chown -R www:www /usr/local /var/www /var/log /usr/local/etc/php/conf.d /etc/cron.d ${MAGENTO_ROOT} ${COMPOSER_HOME}
RUN if [ ! -z "${CRONTAB}" ]; then echo "${CRONTAB}" > /etc/cron.d/magento && touch /var/log/cron.log ; fi

ENTRYPOINT ["/docker-entrypoint.sh"]

WORKDIR ${MAGENTO_ROOT}

USER root

CMD ["bash"]
