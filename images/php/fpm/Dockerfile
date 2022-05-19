{%note%}
FROM golang:1.15 AS builder

RUN if [ $(uname -m) = "x86_64" ]; then mailhog_arch="amd64"; else mailhog_arch="arm64"; fi \
    && wget -O mhsendmail.tar.gz https://github.com/mailhog/mhsendmail/archive/refs/tags/v0.2.0.tar.gz \
    && tar -xf mhsendmail.tar.gz \
    && mkdir -p ./src/github.com/mailhog/ \
    && mv ./mhsendmail-0.2.0 ./src/github.com/mailhog/mhsendmail \
    && cd ./src/github.com/mailhog/mhsendmail/ \
    && go get . \
    && GOOS=linux GOARCH=${mailhog_arch} go build -o mhsendmail .

FROM php:{%version%}-fpm

ARG MAGENTO_ROOT=/app

ENV PHP_MEMORY_LIMIT 2G
ENV PHP_VALIDATE_TIMESTAMPS 1
ENV DEBUG false
ENV MAGENTO_RUN_MODE production
ENV UPLOAD_MAX_FILESIZE 64M
ENV SENDMAIL_PATH /dev/null
ENV PHPRC ${MAGENTO_ROOT}/php.ini

{%env_php_extensions%}

# Install dependencies
RUN apt-get update \
  && apt-get upgrade -y \
  && apt-get install -y --no-install-recommends \
  {%packages%} \
  && rm -rf /var/lib/apt/lists/*

# Install MailHog
COPY --from=builder /go/src/github.com/mailhog/mhsendmail/mhsendmail /usr/local/bin/
RUN sudo chmod +x /usr/local/bin/mhsendmail

# Configure the gd library
{%docker-php-ext-configure%}

# Install required PHP extensions
{%docker-php-ext-install%}

{%php-pecl-extensions%}

{%installation_scripts%}

COPY etc/php-fpm.ini /usr/local/etc/php/conf.d/zz-magento.ini
COPY etc/php-xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug-settings.ini
COPY etc/php-pcov.ini /usr/local/etc/php/conf.d/zz-pcov-settings.ini
COPY etc/mail.ini /usr/local/etc/php/conf.d/zz-mail.ini
COPY etc/php-fpm.conf /usr/local/etc/
COPY etc/php-gnupg.ini /usr/local/etc/php/conf.d/gnupg.ini

RUN groupadd -g 1000 www && useradd -g 1000 -u 1000 -d ${MAGENTO_ROOT} -s /bin/bash www

COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN ["chmod", "+x", "/docker-entrypoint.sh"]

{%volumes_cmd%}

{%volumes_def%}

RUN chown -R www:www /usr/local /var/www /var/log /usr/local/etc/php/conf.d ${MAGENTO_ROOT}

ENTRYPOINT ["/docker-entrypoint.sh"]

WORKDIR ${MAGENTO_ROOT}

USER root

CMD ["php-fpm", "-R"]
