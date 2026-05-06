#!/bin/bash
set -euo pipefail

BUILDER="multiarch-builder"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
source "${ROOT_DIR}/image-matrix.sh"

php_versions_input="${PHP_VERSIONS:-$PHP_VERSIONS_DEFAULT}"
php_types_input="${PHP_TYPES:-$PHP_TYPES_DEFAULT}"
IFS=',' read -r -a phpVersions <<< "${php_versions_input// /}"
IFS=',' read -r -a phpTypes <<< "${php_types_input// /}"

release="${RELEASE_VERSION:-1.4.6}"
run_release="${RUN_RELEASE:-false}"
build_and_push_hash="${BUILD_AND_PUSH_HASH:-false}"
GIT_SHA="$(git rev-parse --short HEAD)"
platforms=("linux/amd64" "linux/arm64")
image="${PHP_IMAGE_REPO:-$PHP_IMAGE_REPO_DEFAULT}"

IMAGES_DIR="${ROOT_DIR}/../images/php"

if ! docker buildx ls | grep -q "$BUILDER"; then
    docker buildx create --use --name "$BUILDER" --driver docker-container
else
    docker buildx use "$BUILDER"
fi
docker buildx inspect --bootstrap "$BUILDER" >/dev/null 2>&1 || true

if [[ ! -d "$IMAGES_DIR" ]]; then
    echo "PHP images directory not found: $IMAGES_DIR"
    exit 1
fi

for version in "${phpVersions[@]}"; do
    for type in "${phpTypes[@]}"; do
        image_dir="${IMAGES_DIR}/${version}-${type}"
        if [[ ! -d "$image_dir" ]]; then
            echo "PHP version directory not found: $image_dir"
            exit 1
        fi

        if [[ "$build_and_push_hash" == "true" ]]; then
            tag="${image}:${version}-${type}-${GIT_SHA}"
        else
            tag="${image}:${version}-${type}-${release}"
        fi

        build_start=$SECONDS
        if [[ "$run_release" == "true" || "$build_and_push_hash" == "true" ]]; then
            echo "Building & pushing: $tag"
            docker buildx build --no-cache                 --platform "$(IFS=,; echo "${platforms[*]}")"                 --provenance=true                 --sbom=true                 -t "$tag"                 --push                 "$image_dir"
        else
            echo "Building (no push): $tag"
            docker buildx build --no-cache                 --platform "$(IFS=,; echo "${platforms[*]}")"                 -t "$tag"                 "$image_dir"
        fi

        build_elapsed=$(( SECONDS - build_start ))
        echo "  -> ${tag} built in $(( build_elapsed / 60 ))m $(( build_elapsed % 60 ))s"
    done
done

echo "Build completed."
