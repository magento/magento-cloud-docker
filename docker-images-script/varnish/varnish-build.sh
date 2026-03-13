#!/bin/bash
set -euo pipefail

BUILDER="multiarch-builder"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
source "${ROOT_DIR}/image-matrix.sh"

varnish_versions_input="${VARNISH_VERSIONS:-$VARNISH_VERSIONS_DEFAULT}"
varnish_multiarch_input="${VARNISH_MULTIARCH_VERSIONS:-$VARNISH_MULTIARCH_VERSIONS_DEFAULT}"
IFS=',' read -r -a varnishVersions <<< "${varnish_versions_input// /}"
IFS=',' read -r -a multiArchVersions <<< "${varnish_multiarch_input// /}"

release="${RELEASE_VERSION:-1.4.6}"
run_release="${RUN_RELEASE:-false}"
build_and_push_hash="${BUILD_AND_PUSH_HASH:-false}"
GIT_SHA="$(git rev-parse --short HEAD)"
image="${VARNISH_IMAGE_REPO:-$VARNISH_IMAGE_REPO_DEFAULT}"
# images/ is at repo root (sibling of docker-images-script/)
IMAGES_DIR="${ROOT_DIR}/../images/varnish"

if ! docker buildx ls | grep -q "$BUILDER"; then
    docker buildx create --use --name "$BUILDER" --driver docker-container
else
    docker buildx use "$BUILDER"
fi
docker buildx inspect --bootstrap "$BUILDER" >/dev/null 2>&1 || true

for version in "${varnishVersions[@]}"; do
    image_dir="${IMAGES_DIR}/${version}"
    if [[ ! -d "$image_dir" ]]; then
        echo "Varnish version directory not found: $image_dir"
        exit 1
    fi

    if [[ "$build_and_push_hash" == "true" ]]; then
        tag="${image}:${version}-${GIT_SHA}"
    else
        tag="${image}:${version}-${release}"
    fi

    if [[ " ${multiArchVersions[*]} " =~ " ${version} " ]]; then
        platforms=("linux/amd64" "linux/arm64")
    else
        platforms=("linux/amd64")
    fi

    build_start=$SECONDS
    if [[ "$run_release" == "true" || "$build_and_push_hash" == "true" ]]; then
        echo "Building & pushing: $tag (${platforms[*]})"
        docker buildx build --no-cache             --platform "$(IFS=,; echo "${platforms[*]}")"             --provenance=true             --sbom=true             -t "$tag"             --push             "$image_dir"
    else
        echo "Building (no push): $tag (${platforms[*]})"
        docker buildx build --no-cache             --platform "$(IFS=,; echo "${platforms[*]}")"             -t "$tag"             "$image_dir"
    fi

    build_elapsed=$(( SECONDS - build_start ))
    echo "  -> ${tag} built in $(( build_elapsed / 60 ))m $(( build_elapsed % 60 ))s"
done

echo "Build completed."
