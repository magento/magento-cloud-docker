#!/bin/bash

if [[ -n "$ES_PLUGINS" ]]; then
  echo "Intalling plugins: $ES_PLUGNS"
  for PLUGIN in $ES_PLUGINS
  do
      ./bin/elasticsearch-plugin install -b "$PLUGIN"
  done
fi
