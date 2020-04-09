#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

function build_push_image() {
  image_name="$1:$2"
  docker build -t "$image_name" "$3"
  docker push "$image_name"
}

function run() {
    service_name="$1"
    service_version="$2"

    if [[ $service_name == "tls" ]]; then
        build_push_image "cloudft/tls" "$TRAVIS_BUILD_NUMBER" "./images/tls"
    else
        if [[ "$service_version" != "" ]]; then
            build_push_image "cloudft/$service_name" "$service_version-$TRAVIS_BUILD_NUMBER" "./images/$service_name/$service_version"
        else
            for service_version in $(ls -1 "./images/$service_name")
            do
                if [[ $service_version == "cli" ]] || [[ $service_version == "fpm" ]]; then continue; fi;
                build_push_image "cloudft/$service_name" "$service_version-$TRAVIS_BUILD_NUMBER" "./images/$service_name/$service_version"
            done
        fi
    fi
}

if [[ $# -gt 0 ]]; then
    run $1 $2
else
    echo "Your command line contains no arguments"
    exit 1
fi