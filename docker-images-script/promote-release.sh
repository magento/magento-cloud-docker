#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/image-matrix.sh"

if [[ -z "${GIT_SHA:-}" ]]; then
    echo "GIT_SHA is required."
    exit 1
fi
if [[ -z "${RELEASE_VERSION:-}" ]]; then
    echo "RELEASE_VERSION is required."
    exit 1
fi

delete_hash_after_promote="${DELETE_HASH_AFTER_PROMOTE:-true}"
allow_overwrite_release="${ALLOW_OVERWRITE_RELEASE:-false}"
targets="${IMAGE_TARGETS:-all}"

image_exists() {
    local ref="$1"
    regctl manifest head "$ref" >/dev/null 2>&1
}

promote_image() {
    local source="$1"
    local target="$2"

    if ! image_exists "$source"; then
        echo "Source image is missing: $source"
        exit 1
    fi

    if image_exists "$target" && [[ "$allow_overwrite_release" != "true" ]]; then
        echo "Target release image already exists: $target"
        echo "Set ALLOW_OVERWRITE_RELEASE=true to overwrite."
        exit 1
    fi

    echo "  Promote: ${source} -> ${target}"
    regctl image copy "$source" "$target"

    if [[ "$delete_hash_after_promote" == "true" ]]; then
        echo "  Delete:  ${source}"
        regctl tag rm "$source"
    fi
}

promote_mailhog() {
    local repo="${MAILHOG_IMAGE_REPO:-$MAILHOG_IMAGE_REPO_DEFAULT}"
    local versions_input="${MAILHOG_VERSIONS:-$MAILHOG_VERSIONS_DEFAULT}"
    IFS=',' read -r -a versions <<< "${versions_input// /}"
    for version in "${versions[@]}"; do
        promote_image "${repo}:${version}-${GIT_SHA}" "${repo}:${version}-${RELEASE_VERSION}"
    done
}

promote_php() {
    local repo="${PHP_IMAGE_REPO:-$PHP_IMAGE_REPO_DEFAULT}"
    local versions_input="${PHP_VERSIONS:-$PHP_VERSIONS_DEFAULT}"
    local types_input="${PHP_TYPES:-$PHP_TYPES_DEFAULT}"
    IFS=',' read -r -a versions <<< "${versions_input// /}"
    IFS=',' read -r -a types <<< "${types_input// /}"
    for version in "${versions[@]}"; do
        for type in "${types[@]}"; do
            promote_image "${repo}:${version}-${type}-${GIT_SHA}" "${repo}:${version}-${type}-${RELEASE_VERSION}"
        done
    done
}

promote_nginx() {
    local repo="${NGINX_IMAGE_REPO:-$NGINX_IMAGE_REPO_DEFAULT}"
    local versions_input="${NGINX_VERSIONS:-$NGINX_VERSIONS_DEFAULT}"
    IFS=',' read -r -a versions <<< "${versions_input// /}"
    for version in "${versions[@]}"; do
        promote_image "${repo}:${version}-${GIT_SHA}" "${repo}:${version}-${RELEASE_VERSION}"
    done
}

promote_varnish() {
    local repo="${VARNISH_IMAGE_REPO:-$VARNISH_IMAGE_REPO_DEFAULT}"
    local versions_input="${VARNISH_VERSIONS:-$VARNISH_VERSIONS_DEFAULT}"
    IFS=',' read -r -a versions <<< "${versions_input// /}"
    for version in "${versions[@]}"; do
        promote_image "${repo}:${version}-${GIT_SHA}" "${repo}:${version}-${RELEASE_VERSION}"
    done
}

promote_elasticsearch() {
    local repo="${ELASTICSEARCH_IMAGE_REPO:-$ELASTICSEARCH_IMAGE_REPO_DEFAULT}"
    local versions_input="${ELASTICSEARCH_VERSIONS:-$ELASTICSEARCH_VERSIONS_DEFAULT}"
    IFS=',' read -r -a versions <<< "${versions_input// /}"
    for version in "${versions[@]}"; do
        promote_image "${repo}:${version}-${GIT_SHA}" "${repo}:${version}-${RELEASE_VERSION}"
    done
}

promote_opensearch() {
    local repo="${OPENSEARCH_IMAGE_REPO:-$OPENSEARCH_IMAGE_REPO_DEFAULT}"
    local versions_input="${OPENSEARCH_VERSIONS:-$OPENSEARCH_VERSIONS_DEFAULT}"
    IFS=',' read -r -a versions <<< "${versions_input// /}"
    for version in "${versions[@]}"; do
        promote_image "${repo}:${version}-${GIT_SHA}" "${repo}:${version}-${RELEASE_VERSION}"
    done
}

run_target() {
    local name="$1"
    local func="$2"
    echo "===================================="
    echo "PROMOTE: ${name}"
    echo "===================================="
    "$func"
    echo "DONE: ${name}"
}

targets="${targets// /}"
if [[ -z "$targets" || "$targets" == "all" ]]; then
    run_target "mail-hog" promote_mailhog
    run_target "php" promote_php
    run_target "nginx" promote_nginx
    run_target "varnish" promote_varnish
    run_target "elasticsearch" promote_elasticsearch
    run_target "opensearch" promote_opensearch
else
    IFS=',' read -r -a list <<< "$targets"
    for item in "${list[@]}"; do
        case "$item" in
            mail-hog) run_target "mail-hog" promote_mailhog ;;
            php) run_target "php" promote_php ;;
            nginx) run_target "nginx" promote_nginx ;;
            varnish) run_target "varnish" promote_varnish ;;
            elasticsearch) run_target "elasticsearch" promote_elasticsearch ;;
            opensearch) run_target "opensearch" promote_opensearch ;;
            *) echo "Unknown image target: $item"; exit 1 ;;
        esac
    done
fi

echo "===================================="
echo "Promote completed: ${GIT_SHA} -> ${RELEASE_VERSION}"
if [[ "$delete_hash_after_promote" == "true" ]]; then
    echo "Hash-tagged images have been removed from DockerHub."
else
    echo "Hash-tagged images were kept (DELETE_HASH_AFTER_PROMOTE=false)."
fi
echo "===================================="
