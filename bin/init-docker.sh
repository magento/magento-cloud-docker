#!/usr/bin/env bash

set -e

# complain to STDERR and exit with error
die()
{
    echo "$*" >&2
    print_usage
    exit 2
}

print_usage()
{
    echo -e "$USAGE"
}

needs_arg()
{
    if [ -z "$OPTARG" ]; then
        OPTARG="${!OPTIND}"
        OPTIND=$(( $OPTIND + 1 ))
    fi
}

parse_bool_flag()
{
    case "$(echo $OPTARG | tr '[:upper:]' '[:lower:]')" in
        y | yes | t | true | 1 )
            OPTARG=true
            ;;
        n | no | f | false | 0 )
            OPTARG=false
            ;;
        * )
            die "Invalid value $OPTARG for $OPT"
            ;;
    esac
}

version_is_valid()
{
    if [[ ! $OPTARG =~ ^[0-9]+\.[0-9]+$ ]]; then
        die "Invalid version number $OPTARG for $OPT"
    fi
}

domain_is_valid()
{
    # Check that a flag isn't being interpreted as the domain
    if [[ -z $OPTARG ]] || [[ $OPTARG == -* ]]; then
        die "Invalid domain $OPTARG for $OPT"
    fi
}

composer_install()
{
    docker run --rm -e "MAGENTO_ROOT=/app" -v "$(pwd)":/app -v ~/.composer/cache:/composer/cache "magento/magento-cloud-docker-php:${PHP_VERSION}-cli-${IMAGE_VERSION}" composer install --ansi
}

add_host()
{
    if grep -Eq "^\s*\d+\.\d+\.\d+\.\d+\s+${DOMAIN}$" /etc/hosts; then
        echo -e "\033[33m\033[1mThere is already an entry for $DOMAIN in /etc/hosts, skipping.\033[0m"

        return
    fi

    echo "127.0.0.1 $DOMAIN" | sudo tee -a /etc/hosts
}

PHP_VERSION="7.4"
IMAGE_VERSION="1.2.1"
ADD_HOST=true
DOMAIN="magento2.docker"
USAGE="Init Docker

\033[33mDescription:\033[0m
  Initialize a Magento Cloud Docker based project

\033[33mOptions:\033[0m
  \033[32m-p, --php\033[0m        PHP version (for installing dependencies) \033[33m[default: ${PHP_VERSION}]\033[0m
  \033[32m-i, --image\033[0m      image version (for installing dependencies) \033[33m[default: ${IMAGE_VERSION}]\033[0m
  \033[32m    --host\033[0m       domain name to add to /etc/hosts \033[33m[default: ${DOMAIN}]\033[0m
  \033[32m    --add-host\033[0m   add domain name to /etc/hosts file \033[33m[default: ${ADD_HOST}]\033[0m
  \033[32m-h, --help\033[0m       show this help text

\033[33mExample usage:\033[0m
  \033[32mbin/init-docker.sh\033[0m                           perform default actions
  \033[32mbin/init-docker.sh --php 7.3 --add-host no\033[0m   use PHP 7.3, skip adding domain to /etc/hosts"

while getopts "hp:i:-:" OPT; do
    if [ "$OPT" = "-" ]; then   # long option: reformulate OPT and OPTARG
        OPT="${OPTARG%%=*}"       # extract long option name

        OPTARG="${OPTARG#$OPT}"   # extract long option argument (may be empty)
        OPTARG="${OPTARG#=}"      # if long option argument, remove assigning `=`
    fi

    case "$OPT" in
        p | php )
            needs_arg "$@"
            version_is_valid
            PHP_VERSION="$OPTARG"
            ;;

        i | image )
            needs_arg "$@"
            version_is_valid
            IMAGE_VERSION="$OPTARG"
            ;;

        add-host )
            needs_arg "$@"
            parse_bool_flag
            ADD_HOST="$OPTARG"
            ;;

        host )
            needs_arg "$@"
            domain_is_valid
            DOMAIN="$OPTARG"
            ;;

        h | help )
            print_usage
            exit 0
            ;;

        \? )
            print_usage
            exit 1
            ;;

        ??* )
            die "Illegal option --$OPT"
            ;;
    esac
done

shift $((OPTIND-1)) # remove parsed options and args from $@ list

echo -e "\033[32m\033[1mInstalling Composer Packages\033[0m"
composer_install

if [ $ADD_HOST == true ]; then
    echo -e "\033[32m\033[1mAdding $DOMAIN to /etc/hosts\033[0m"
    echo -e "Your system password may be required"
    add_host
fi
