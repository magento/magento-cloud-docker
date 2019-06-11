#!/bin/bash

[ "$DEBUG" = "true" ] && set -x

# Ensure our Magento directory exists
mkdir -p $MAGENTO_ROOT

# Configure Sendmail if required
if [ "$ENABLE_SENDMAIL" == "true" ]; then
    /etc/init.d/sendmail start
fi

# Enable PHP extensions
PHP_EXT_DIR=/usr/local/etc/php/conf.d/
PHP_EXT_COM_ON=docker-php-ext-enable

if [ -d ${PHP_EXT_DIR} ] && [ hash ${PHP_EXT_COM_ON} 2>/dev/null ] && [[ -v ${PHP_EXTENSIONS} ]]; then
    shopt -q extglob; extglob_set=$?
    ((extglob_set)) && shopt -s extglob
    rm -f "$PHP_EXT_DIR!(zz-magento.ini|zz-xdebug-settings.ini|zz-mail.ini)"
    ((extglob_set)) && shopt -u extglob
    ${PHP_EXT_COM_ON} ${PHP_EXTENSIONS}
fi

# Substitute in php.ini values
[ ! -z "${PHP_MEMORY_LIMIT}" ] && sed -i "s/!PHP_MEMORY_LIMIT!/${PHP_MEMORY_LIMIT}/" /usr/local/etc/php/conf.d/zz-magento.ini
[ ! -z "${UPLOAD_MAX_FILESIZE}" ] && sed -i "s/!UPLOAD_MAX_FILESIZE!/${UPLOAD_MAX_FILESIZE}/" /usr/local/etc/php/conf.d/zz-magento.ini

# Configure PHP-FPM
[ ! -z "${MAGENTO_RUN_MODE}" ] && sed -i "s/!MAGENTO_RUN_MODE!/${MAGENTO_RUN_MODE}/" /usr/local/etc/php-fpm.conf

exec "$@"

