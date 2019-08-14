#!/bin/bash
mutagen create \
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
