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
## Generating new PHP configuration

To generate configuration for new version of PHP, run next command:

```
php ./bin/mcd generate:php <version>
```

Where:

- `version`: Version of PHP to be generated
