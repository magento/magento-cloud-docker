#!/bin/bash
set -eo pipefail

fpm_response=$(curl -o /dev/null -s -w "%{http_code}\n" http://${WEB_HOST:-web}:${WEB_PORT:-80}/status)

if [ "$fpm_response" == "200" ]
then
        exit 0
else
        echo "The health of the FPM server is not good"
        exit 1
fi

