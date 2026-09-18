#!/bin/bash
set -eo pipefail

if [[ -n "$OS_PLUGINS" ]]; then
  echo "Installing plugins: $OS_PLUGINS"
  for PLUGIN in $OS_PLUGINS
  do
      ./bin/opensearch-plugin install -b "$PLUGIN"
  done
fi

/bin/bash /usr/share/opensearch/opensearch-docker-entrypoint.sh
