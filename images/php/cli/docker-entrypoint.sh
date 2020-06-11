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

CRON_LOG=/var/log/cron.log

if [ ! -z "${CRONTAB}" ]; then
    echo "${CRONTAB}" > /etc/cron.d/magento
fi

touch $CRON_LOG

PHP_EXT_DIR=/usr/local/etc/php/conf.d

# Configure Sendmail if required
if [ "$ENABLE_SENDMAIL" == "true" ]; then
    sed -i "s/!SENDMAIL_PATH!/\/usr\/sbin\/sendmail -t -i/" ${PHP_EXT_DIR}/zz-mail.ini
    /etc/init.d/sendmail start
else
    sed -i "s/!SENDMAIL_PATH!/\"true > \/dev\/null\"/" ${PHP_EXT_DIR}/zz-mail.ini
fi

# Substitute in php.ini values
[ ! -z "${PHP_MEMORY_LIMIT}" ] && sed -i "s/!PHP_MEMORY_LIMIT!/${PHP_MEMORY_LIMIT}/" ${PHP_EXT_DIR}/zz-magento.ini
[ ! -z "${UPLOAD_MAX_FILESIZE}" ] && sed -i "s/!UPLOAD_MAX_FILESIZE!/${UPLOAD_MAX_FILESIZE}/" ${PHP_EXT_DIR}/zz-magento.ini

# Add custom php.ini if it exists
[ -f "/app/php.ini" ] && cp /app/php.ini ${PHP_EXT_DIR}/zzz-custom-php.ini

# Enable PHP extensions
PHP_EXT_COM_ON=docker-php-ext-enable

[ -d ${PHP_EXT_DIR} ] && rm -f ${PHP_EXT_DIR}/docker-php-ext-*.ini

if [ -x "$(command -v ${PHP_EXT_COM_ON})" ] && [ ! -z "${PHP_EXTENSIONS}" ]; then
      ${PHP_EXT_COM_ON} ${PHP_EXTENSIONS}
fi


# Configure composer
[ ! -z "${COMPOSER_GITHUB_TOKEN}" ] && \
    composer config --global github-oauth.github.com $COMPOSER_GITHUB_TOKEN

[ ! -z "${COMPOSER_MAGENTO_USERNAME}" ] && \
    composer config --global http-basic.repo.magento.com \
        $COMPOSER_MAGENTO_USERNAME $COMPOSER_MAGENTO_PASSWORD

exec "$@"
