#!/bin/bash

# Shared image matrix defaults for build and promote flows.
# Keep this file as the single source of truth for repos/versions.

SUPPORTED_TARGETS=("mail-hog" "php" "nginx" "varnish" "elasticsearch" "opensearch")

MAILHOG_IMAGE_REPO_DEFAULT="magento/magento-cloud-docker-mailhog"
MAILHOG_VERSIONS_DEFAULT="1.0"

PHP_IMAGE_REPO_DEFAULT="magento/magento-cloud-docker-php"
PHP_VERSIONS_DEFAULT="7.2,7.3,7.4,8.0,8.1,8.2,8.3,8.4,8.5"
PHP_TYPES_DEFAULT="cli,fpm"

NGINX_IMAGE_REPO_DEFAULT="magento/magento-cloud-docker-nginx"
NGINX_VERSIONS_DEFAULT="1.24"

VARNISH_IMAGE_REPO_DEFAULT="magento/magento-cloud-docker-varnish"
VARNISH_VERSIONS_DEFAULT="6.0,6.2,6.5,6.6,7.0,7.1,7.1.1"
VARNISH_MULTIARCH_VERSIONS_DEFAULT="6.0,6.6,7.0,7.1,7.1.1"

ELASTICSEARCH_IMAGE_REPO_DEFAULT="magento/magento-cloud-docker-elasticsearch"
ELASTICSEARCH_VERSIONS_DEFAULT="7.10,7.11"

OPENSEARCH_IMAGE_REPO_DEFAULT="magento/magento-cloud-docker-opensearch"
OPENSEARCH_VERSIONS_DEFAULT="1.1,1.2,1.3,2.3,2.4,2.5,2.12,3"
