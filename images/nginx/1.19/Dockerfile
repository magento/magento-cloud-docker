FROM nginx:1.19

ENV UPLOAD_MAX_FILESIZE 64M
ENV XDEBUG_HOST fpm_xdebug
ENV FPM_HOST fpm
ENV FPM_PORT 9000
ENV UPSTREAM_HOST web
ENV UPSTREAM_PORT 8080
ENV MAGENTO_ROOT /app
ENV MAGENTO_RUN_MODE production
ENV MFTF_UTILS 0
ENV DEBUG false
ENV NGINX_WORKER_PROCESSES 1
ENV NGINX_WORKER_CONNECTIONS 1024

COPY etc/nginx.conf /etc/nginx/
COPY etc/vhost.conf /etc/nginx/conf.d/default.conf
COPY etc/xdebug-upstream.conf /etc/nginx/conf.d/xdebug/upstream.conf

RUN mkdir /etc/nginx/ssl

RUN apt-get update && \
    apt-get install -y openssl

RUN openssl req -x509 -newkey rsa:2048 -sha256 -days 730 -nodes \
  -keyout /etc/nginx/ssl/magento.key -out /etc/nginx/ssl/magento.crt \
  -subj "/C=US/ST=TX/L=Austin/O=Adobe Commerce/OU=Cloud Docker/CN=magento2.docker" \
  -addext "subjectAltName=DNS:magento2.docker,DNS:www.magento2.docker"

VOLUME ${MAGENTO_ROOT}

COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN ["chmod", "+x", "/docker-entrypoint.sh"]
ENTRYPOINT ["/docker-entrypoint.sh"]

EXPOSE 443

WORKDIR ${MAGENTO_ROOT}

CMD ["nginx", "-g", "daemon off;"]
