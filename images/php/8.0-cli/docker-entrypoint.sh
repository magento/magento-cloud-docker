#!/bin/bash

[ "$DEBUG" = "true" ] && set -x

CRON_LOG=/var/log/cron.log

if [ ! -z "${CRONTAB}" ]; then
    echo "${CRONTAB}" > /etc/cron.d/magento
fi

touch $CRON_LOG

PHP_EXT_DIR=/usr/local/etc/php/conf.d

# Configure Sendmail if required
if [ "$ENABLE_SENDMAIL" == "true" ]; then
    sed -i "s/!SENDMAIL_PATH!/\"\/usr\/local\/bin\/mhsendmail --smtp-addr=mailhog:1025\"/" ${PHP_EXT_DIR}/zz-mail.ini
else
    sed -i "s/!SENDMAIL_PATH!/\"true > \/dev\/null\"/" ${PHP_EXT_DIR}/zz-mail.ini
fi

# Substitute in php.ini values
[ ! -z "${PHP_MEMORY_LIMIT}" ] && sed -i "s/!PHP_MEMORY_LIMIT!/${PHP_MEMORY_LIMIT}/" ${PHP_EXT_DIR}/zz-magento.ini
[ ! -z "${UPLOAD_MAX_FILESIZE}" ] && sed -i "s/!UPLOAD_MAX_FILESIZE!/${UPLOAD_MAX_FILESIZE}/" ${PHP_EXT_DIR}/zz-magento.ini

# Add custom php.ini if it exists
[ -f "/app/php.ini" ] && cp /app/php.ini ${PHP_EXT_DIR}/zzz-custom-php.ini

# Add developer php.ini if it exists
[ -f "/app/php.dev.ini" ] && [ "$MAGENTO_RUN_MODE" == "developer" ] && cp /app/php.dev.ini ${PHP_EXT_DIR}/zzz-dev-php.ini

# Enable PHP extensions
PHP_EXT_COM_ON=docker-php-ext-enable

[ -d ${PHP_EXT_DIR} ] && rm -f ${PHP_EXT_DIR}/docker-php-ext-*.ini

if [ -x "$(command -v ${PHP_EXT_COM_ON})" ] && [ ! -z "${PHP_EXTENSIONS}" ]; then
      ${PHP_EXT_COM_ON} ${PHP_EXTENSIONS}
fi

# Configure composer
[ ! -z "${COMPOSER_VERSION}" ] && \
    composer self-update $COMPOSER_VERSION

[ ! -z "${COMPOSER_GITHUB_TOKEN}" ] && \
    composer config --global github-oauth.github.com $COMPOSER_GITHUB_TOKEN

[ ! -z "${COMPOSER_MAGENTO_USERNAME}" ] && \
    composer config --global http-basic.repo.magento.com \
        $COMPOSER_MAGENTO_USERNAME $COMPOSER_MAGENTO_PASSWORD

exec "$@"
