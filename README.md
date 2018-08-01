# Magento Commerce (Cloud) Docker

[![Docker Build Status][ico-dockerbuild]][link-dockerhub]
[![Docker Pulls][ico-downloads]][link-dockerhub]
[![Docker Stars][ico-dockerstars]][link-dockerhub]

A collection of build configurations to emulate Magento Cloud environment locally. Includes next services:

- PHP CLI
- PHP FPM
- NGINX 1.9+
- Redis
- Varnish

## Docker Hub

https://hub.docker.com/r/magento/

## Credits

Inspired by [meanbee/docker-magento2](https://github.com/meanbee/docker-magento2)

# Usage

## Installation

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

```bash
docker-compose run cli bash
```

### Running Magento command

```bash
docker-compose run cli magento-command
```

### Running ECE-Tools command

```bash
docker-compose run cli ece-command
```

[ico-dockerbuild]: https://img.shields.io/docker/build/magento/magento-cloud-docker-php.svg?style=flat-square
[ico-downloads]: https://img.shields.io/docker/pulls/magento/magento-cloud-docker-php.svg?style=flat-square
[ico-dockerstars]: https://img.shields.io/docker/stars/magento/magento-cloud-docker-php.svg?style=flat-square

[link-dockerhub]: https://hub.docker.com/r/magento/magento-cloud-docker-php
