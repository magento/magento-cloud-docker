#!/bin/bash

[ "$DEBUG" = "true" ] && set -x

# If asked, we'll ensure that the www is set to the same uid/gid as the
# mounted volume.  This works around permission issues with virtualbox shared
# folders.
if [[ "$UPDATE_UID_GID" = "true" ]]; then
    echo "Updating www uid and gid"

    DOCKER_UID=`stat -c "%u" $MAGENTO_ROOT`
    DOCKER_GID=`stat -c "%g" $MAGENTO_ROOT`

    INCUMBENT_USER=`getent passwd $DOCKER_UID | cut -d: -f1`
    INCUMBENT_GROUP=`getent group $DOCKER_GID | cut -d: -f1`

    echo "Docker: uid = $DOCKER_UID, gid = $DOCKER_GID"
    echo "Incumbent: user = $INCUMBENT_USER, group = $INCUMBENT_GROUP"

    # Once we've established the ids and incumbent ids then we need to free them
    # up (if necessary) and then make the change to www.

    [ ! -z "${INCUMBENT_USER}" ] && usermod -u 99$DOCKER_UID $INCUMBENT_USER
    usermod -u $DOCKER_UID www

    [ ! -z "${INCUMBENT_GROUP}" ] && groupmod -g 99$DOCKER_GID $INCUMBENT_GROUP
    groupmod -g $DOCKER_GID www
fi

# Ensure our Magento directory exists
mkdir -p $MAGENTO_ROOT

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

# Configure PHP-FPM
[ ! -z "${MAGENTO_RUN_MODE}" ] && sed -i "s/!MAGENTO_RUN_MODE!/${MAGENTO_RUN_MODE}/" /usr/local/etc/php-fpm.conf

# Use www user on FPM pool when on developer mode
# to avoid permission issues with mounted volumes.
if [ "$MAGENTO_RUN_MODE" == "developer" ]; then
    sed -i 's/user = .*/user = www/' /usr/local/etc/php-fpm.conf
    sed -i 's/group = .*/group = www/' /usr/local/etc/php-fpm.conf
fi

# Set host.docker.inernal if not available
HOST_NAME="host.docker.internal"
HOST_IP=$(php -r "putenv('RES_OPTIONS=retrans:1 retry:1 timeout:1 attempts:1'); echo gethostbyname('$HOST_NAME');")
if [[ "$HOST_IP" == "$HOST_NAME" ]]; then
  HOST_IP=$(/sbin/ip route|awk '/default/ { print $3 }')
  printf "\n%s %s\n" "$HOST_IP" "$HOST_NAME" >> /etc/hosts
fi

exec "$@"
