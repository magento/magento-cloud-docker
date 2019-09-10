#!/bin/bash
mutagen create \
       --label=magento-docker \
       --default-group-beta=www \
       --default-owner-beta=www \
       --sync-mode=two-way-resolved \
       --default-file-mode=0644 \
       --default-directory-mode=0755 \
       --ignore=/.idea \
       --ignore=/.magento \
       --ignore=/.docker \
       --ignore=/.github \
       --ignore-vcs \
       --symlink-mode=posix-raw \
       ./ docker://$(docker-compose ps -q fpm|awk '{print $1}')/app
