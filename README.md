# Introduction

A collection of build configurations for Magento Cloud Docker images. Includes:

- PHP CLI
- PHP FPM
- NGINX 1.9+

## Docker Hub

https://hub.docker.com/r/magento/

## Credits

Inspired by https://github.com/meanbee/docker-magento2

# Usage

## Instalaltion

1. To be able to use this Docker configuration, you must have cloned [Magento Cloud](https://github.com/magento/magento-cloud)  project
1. Follow instruction on [DevDocs](https://devdocs.magento.com/guides/v2.2/cloud/reference/docker-config.html)

## Generating new PHP configuration

To generate configuration for new version of PHP, run next command:

```
php ./bin/mcd generate:php <version>
```

Where:

- `version`: Version of PHP to be generated

## Executing commands

### Connecting to CLI container
- Connect to CLI container by running `docker-compose run cli bash`

### Running commands directly
- `docker-compose run cli ece-command` for ECE-Tools specific commands
- `docker-compose run cli magento-command` for Magento 2 specifiic commands
