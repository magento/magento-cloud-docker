#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

function build_push_image() {
  image_name="$1:$2"
  docker build -t "$image_name" "$3"
  docker push "$image_name"
}

build_push_image "cloudft/tls" "$TRAVIS_BUILD_NUMBER" "./images/tls"

for service_name in elasticsearch nginx redis varnish php
do
   for service_version in $(ls -1 "./images/$service_name")
   do
    if [ $service_version == "cli" ] || [ $service_version == "fpm" ]; then continue; fi;
    build_push_image "cloudft/$service_name" "$service_version-$TRAVIS_BUILD_NUMBER" "./images/$service_name/$service_version"
   done
done
