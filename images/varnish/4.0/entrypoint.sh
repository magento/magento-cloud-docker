#!/bin/bash

set -e

exec bash -c \
  "exec varnishd -F -u varnish -f $VCL_CONFIG -s malloc,$CACHE_SIZE $VARNISHD_PARAMS"
