#!/bin/bash
set -eo pipefail

if health="$(curl -fsSL "http://${OS_HOST:-opensearch}:${OS_HOST:-9200}/_cat/health?h=status")"; then
  health="$(echo "$health" | sed -r 's/^[[:space:]]+|[[:space:]]+$//g')" # trim whitespace (otherwise we'll have "green ")
  if [ "$health" = 'green' ] || [ "$health" = 'yellow' ]; then
    exit 0
  fi
  echo >&2 "Unexpected health status: $health"
fi

exit 1
