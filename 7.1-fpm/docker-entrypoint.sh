#!/bin/bash

[ "$DEBUG" = "true" ] && set -x

# Ensure our Magento directory exists
mkdir -p $MAGENTO_ROOT
if setfacl -R -d -m "g:www-data:7" $MAGENTO_ROOT && setfacl -R -m "g:www-data:7" $MAGENTO_ROOT
then 
     echo "Adding permissions for www-data group via setfacl"
     MAGENTO_ROOT_OWNER=$(ls -ld $MAGENTO_ROOT | awk '{print $3}')
     setfacl -R -d -m "u:${MAGENTO_ROOT_OWNER}:7" $MAGENTO_ROOT
     setfacl -R -m "u:${MAGENTO_ROOT_OWNER}:7" $MAGENTO_ROOT
else 
     echo "Changing permissions to www-data user and group via chown"
     chown -R www-data:www-data $MAGENTO_ROOT
fi

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

