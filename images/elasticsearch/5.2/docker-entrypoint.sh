#!/bin/bash

set -e

# Add elasticsearch as command if needed
if [ "${1:0:1}" = '-' ]; then
	set -- elasticsearch "$@"
fi

# Drop root privileges if we are running elasticsearch
# allow the container to be started with `--user`
if [ "$1" = 'elasticsearch' -a "$(id -u)" = '0' ]; then
	# Change the ownership of user-mutable directories to elasticsearch
	for path in \
		/usr/share/elasticsearch/data \
		/usr/share/elasticsearch/logs \
	; do
		chown -R elasticsearch:elasticsearch "$path"
	done

  es_opts=''

  while IFS='=' read -r envvar_key envvar_value
  do
      # Elasticsearch env vars need to have at least two dot separated lowercase words, e.g. `cluster.name`
      if [[ "$envvar_key" =~ ^[a-z]+\.[a-z]+ ]]
      then
          if [[ ! -z $envvar_value ]]; then
            es_opt="-E${envvar_key}=${envvar_value}"
            es_opts+=" ${es_opt}"
          fi
      fi
  done < <(env)

	set -- gosu elasticsearch "$@" ${es_opts}
	#exec gosu elasticsearch "$BASH_SOURCE" "$@"
fi

# As argument is not related to elasticsearch,
# then assume that user wants to run his own process,
# for example a `bash` shell to explore this image
exec "$@"
