#!/bin/bash

set -eo pipefail

nginx_response=$(curl -o /dev/null -s -w "%{http_code}\n" http://localhost/nginx_status)

if [ "$nginx_response" == "200" ]
then
	exit 0
else
	echo "The health of the nginx server is not good"
	exit 1
fi

