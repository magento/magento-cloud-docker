#!/bin/bash
mutagen sync terminate --label-selector=magento-docker
mutagen sync terminate --label-selector=magento-docker-vendor

mutagen sync create \
       --label=magento-docker \
       --sync-mode=two-way-resolved \
       --default-file-mode=0644 \
       --default-directory-mode=0755 \
       --ignore=/.idea \
       --ignore=/.magento \
       --ignore=/.docker \
       --ignore=/.github \
       --ignore=/vendor \
       --ignore=*.sql \
       --ignore=*.gz \
       --ignore=*.zip \
       --ignore=*.bz2 \
       --ignore-vcs \
       --symlink-mode=posix-raw \
       ./ docker://$(docker-compose ps -q fpm|awk '{print $1}')/app

mutagen sync create \
       --label=magento-docker-vendor \
       --sync-mode=two-way-resolved \
       --default-file-mode=0644 \
       --default-directory-mode=0755 \
       --symlink-mode=posix-raw \
       ./vendor docker://$(docker-compose ps -q fpm|awk '{print $1}')/app/vendor
