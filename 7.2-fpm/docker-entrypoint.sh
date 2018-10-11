#!/bin/bash

[ "$DEBUG" = "true" ] && set -x

# Ensure our Magento directory exists
mkdir -p $MAGENTO_ROOT

# Configure Sendmail if required
if [ "$ENABLE_SENDMAIL" == "true" ]; then
    /etc/init.d/sendmail start
fi

# Substitute in php.ini values
[ ! -z "${PHP_MEMORY_LIMIT}" ] && sed -i "s/!PHP_MEMORY_LIMIT!/${PHP_MEMORY_LIMIT}/" /usr/local/etc/php/conf.d/zz-magento.ini
[ ! -z "${UPLOAD_MAX_FILESIZE}" ] && sed -i "s/!UPLOAD_MAX_FILESIZE!/${UPLOAD_MAX_FILESIZE}/" /usr/local/etc/php/conf.d/zz-magento.ini

[ "$PHP_ENABLE_XDEBUG" = "true" ] && \
    docker-php-ext-enable xdebug && \
    echo "Xdebug is enabled"

# Configure PHP-FPM
[ ! -z "${MAGENTO_RUN_MODE}" ] && sed -i "s/!MAGENTO_RUN_MODE!/${MAGENTO_RUN_MODE}/" /usr/local/etc/php-fpm.conf

exec "$@"

