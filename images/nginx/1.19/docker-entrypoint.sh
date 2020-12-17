#!/bin/bash

[ "$DEBUG" = "true" ] && set -x

VHOST_FILE="/etc/nginx/conf.d/default.conf"
NGINX_FILE="/etc/nginx/nginx.conf"
XDEBUG_UPSTREAM_FILE="/etc/nginx/conf.d/xdebug/upstream.conf"

[ ! -z "${FPM_HOST}" ] && sed -i "s/!FPM_HOST!/${FPM_HOST}/" $VHOST_FILE
[ ! -z "${XDEBUG_HOST}" ] && sed -i "s/!XDEBUG_HOST!/${XDEBUG_HOST}/" $XDEBUG_UPSTREAM_FILE
[ ! -z "${FPM_PORT}" ] && sed -i "s/!FPM_PORT!/${FPM_PORT}/" $VHOST_FILE
[ ! -z "${FPM_PORT}" ] && sed -i "s/!FPM_PORT!/${FPM_PORT}/" $XDEBUG_UPSTREAM_FILE
[ ! -z "${UPSTREAM_HOST}" ] && sed -i "s/!UPSTREAM_HOST!/${UPSTREAM_HOST}/" $VHOST_FILE
[ ! -z "${UPSTREAM_PORT}" ] && sed -i "s/!UPSTREAM_PORT!/${UPSTREAM_PORT}/" $VHOST_FILE
[ ! -z "${MAGENTO_ROOT}" ] && sed -i "s#!MAGENTO_ROOT!#${MAGENTO_ROOT}#" $VHOST_FILE
[ ! -z "${MAGENTO_RUN_MODE}" ] && sed -i "s/!MAGENTO_RUN_MODE!/${MAGENTO_RUN_MODE}/" $VHOST_FILE
[ ! -z "${MFTF_UTILS}" ] && sed -i "s/!MFTF_UTILS!/${MFTF_UTILS}/" $VHOST_FILE
[ ! -z "${UPLOAD_MAX_FILESIZE}" ] && sed -i "s/!UPLOAD_MAX_FILESIZE!/${UPLOAD_MAX_FILESIZE}/" $VHOST_FILE
[ ! -z "${WITH_XDEBUG}" ] && sed -i "s/!WITH_XDEBUG!/${WITH_XDEBUG}/" $VHOST_FILE
[ "${WITH_XDEBUG}" == "1" ] && sed -i "s/#include_xdebug_upstream/include/" $NGINX_FILE
[ ! -z "${NGINX_WORKER_PROCESSES}" ] && sed -i "s/!NGINX_WORKER_PROCESSES!/${NGINX_WORKER_PROCESSES}/" $NGINX_FILE
[ ! -z "${NGINX_WORKER_CONNECTIONS}" ] && sed -i "s/!NGINX_WORKER_CONNECTIONS!/${NGINX_WORKER_CONNECTIONS}/" $NGINX_FILE

# Check if the nginx syntax is fine, then launch.
nginx -t

exec "$@"
