#!/bin/bash

[ "$DEBUG" = "true" ] && set -x

if [ ! -z "${CRONTAB}" ]; then
    echo "${CRONTAB}" > /etc/cron.d/magento && touch /var/log/cron.log
fi

PHP_EXT_DIR=/usr/local/etc/php/conf.d

# Enable PHP extensions
PHP_EXT_COM_ON=docker-php-ext-enable

[ -d ${PHP_EXT_DIR} ] && rm -f ${PHP_EXT_DIR}/docker-php-ext-*.ini

if [ -x "$(command -v ${PHP_EXT_COM_ON})" ] && [ ! -z "${PHP_EXTENSIONS}" ]; then
  ${PHP_EXT_COM_ON} ${PHP_EXTENSIONS}
fi

# Clear composer cache if needed
[ "$COMPOSER_CLEAR_CACHE" = "true" ] && \
    composer clearcache

# Configure composer
[ ! -z "${COMPOSER_VERSION}" ] && \
    composer self-update $COMPOSER_VERSION

[ ! -z "${COMPOSER_GITHUB_TOKEN}" ] && \
    composer config --global github-oauth.github.com $COMPOSER_GITHUB_TOKEN

[ ! -z "${COMPOSER_MAGENTO_USERNAME}" ] && \
    composer config --global http-basic.repo.magento.com \
        $COMPOSER_MAGENTO_USERNAME $COMPOSER_MAGENTO_PASSWORD

exec "$@"
