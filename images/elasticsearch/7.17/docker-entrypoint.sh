#!/bin/bash
set -eo pipefail

if [[ -n "$ES_PLUGINS" ]]; then
  echo "Installing plugins: $ES_PLUGNS"
  for PLUGIN in $ES_PLUGINS
  do
      ./bin/elasticsearch-plugin install -b "$PLUGIN"
  done
fi

/bin/bash /usr/local/bin/docker-entrypoint.sh
