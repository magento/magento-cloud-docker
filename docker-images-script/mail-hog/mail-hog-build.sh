#!/bin/bash
set -euo pipefail

BUILDER="multiarch-builder"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
source "${ROOT_DIR}/image-matrix.sh"

mailhog_versions_input="${MAILHOG_VERSIONS:-$MAILHOG_VERSIONS_DEFAULT}"
IFS=',' read -r -a mailhogVersions <<< "${mailhog_versions_input// /}"

release="${RELEASE_VERSION:-1.4.6}"
run_release="${RUN_RELEASE:-false}"
build_and_push_hash="${BUILD_AND_PUSH_HASH:-false}"
GIT_SHA="$(git rev-parse --short HEAD)"
platforms=("linux/amd64" "linux/arm64")
image="${MAILHOG_IMAGE_REPO:-$MAILHOG_IMAGE_REPO_DEFAULT}"
# images/ is at repo root (sibling of docker-images-script/)
IMAGES_DIR="${ROOT_DIR}/../images"

if docker buildx inspect "$BUILDER" >/dev/null 2>&1; then
    docker buildx use "$BUILDER"
else
    docker buildx create --name "$BUILDER" --use --driver docker-container
fi
docker buildx inspect --bootstrap "$BUILDER" >/dev/null 2>&1 || true

for version in "${mailhogVersions[@]}"; do
    echo "=============================="
    echo "Building Mailhog version: $version"
    echo "=============================="

    IMAGE_PATH="${IMAGES_DIR}/mailhog/${version}"
    if [[ ! -d "$IMAGE_PATH" ]]; then
        mkdir -p "$IMAGE_PATH"
    fi

    write_dockerfile() {
        cat > "$IMAGE_PATH/Dockerfile" << 'DOCKERFILE'
FROM golang:1.24-alpine AS builder
ARG MAILHOG_VERSION=1.0.1
ARG TARGETARCH

RUN apk add --no-cache git

ENV CGO_ENABLED=0
RUN GOOS=linux GOARCH="${TARGETARCH}"     go install "github.com/mailhog/MailHog@v${MAILHOG_VERSION}"

FROM alpine:3.19

RUN apk add --no-cache     ca-certificates     wget     && rm -rf /var/cache/apk/*

COPY --from=builder /go/bin/MailHog /usr/local/bin/MailHog

# Create non-root user for better security
RUN addgroup -g 1000 mailhog     && adduser -u 1000 -G mailhog -s /bin/sh -D mailhog

# Switch to non-root user
USER mailhog

EXPOSE 1025 8025

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3     CMD wget --no-verbose --tries=1 --spider http://localhost:8025/api/v2/messages || exit 1

ENTRYPOINT ["MailHog"]
DOCKERFILE
    }

    if [[ ! -f "$IMAGE_PATH/Dockerfile" ]]; then
        write_dockerfile
    elif grep -q "apk add" "$IMAGE_PATH/Dockerfile" && grep -qi "mailhog" "$IMAGE_PATH/Dockerfile"; then
        write_dockerfile
    elif grep -q "MailHog_linux_" "$IMAGE_PATH/Dockerfile"; then
        write_dockerfile
    elif grep -q "curl -fsSL -o /usr/local/bin/MailHog" "$IMAGE_PATH/Dockerfile"; then
        write_dockerfile
    elif grep -q "FROM golang:1.22-alpine" "$IMAGE_PATH/Dockerfile"; then
        write_dockerfile
    fi

    cd "$IMAGE_PATH" || exit 1

    if [[ "$build_and_push_hash" == "true" ]]; then
        tag="${image}:${version}-${GIT_SHA}"
    else
        tag="${image}:${version}-${release}"
    fi

    build_start=$SECONDS
    if [[ "$run_release" == "true" || "$build_and_push_hash" == "true" ]]; then
        echo "Building & pushing: $tag"
        docker buildx build --no-cache             --platform "$(IFS=,; echo "${platforms[*]}")"             --provenance=true             --sbom=true             -t "$tag"             --push .
    else
        echo "Building (no push): $tag"
        docker buildx build --no-cache             --platform "$(IFS=,; echo "${platforms[*]}")"             -t "$tag" .
    fi

    build_elapsed=$(( SECONDS - build_start ))
    echo "  -> ${tag} built in $(( build_elapsed / 60 ))m $(( build_elapsed % 60 ))s"
    cd "$SCRIPT_DIR"
done

echo "Build completed."
