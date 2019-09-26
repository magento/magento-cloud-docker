FROM nginx:1.9

COPY etc/nginx.conf /etc/nginx/
COPY etc/vhost.conf /etc/nginx/conf.d/default.conf

RUN touch /var/run/nginx.pid

EXPOSE 80 443

ENV UPLOAD_MAX_FILESIZE 64M
ENV FPM_HOST fpm
ENV FPM_PORT 9000
ENV MAGENTO_ROOT /app
ENV MAGENTO_RUN_MODE production
ENV DEBUG false
ENV UID 1000
ENV GID 1000
ENV UPDATE_UID_GID false

RUN groupadd -g ${GID} www && useradd -g ${GID} -u ${UID} -d ${MAGENTO_ROOT} -s /bin/bash www
RUN chown -R www:www /etc/nginx /var/cache/nginx /var/run/nginx.pid

RUN apt-get update && apt-get install -y openssl
RUN mkdir /etc/nginx/ssl \
  && echo -e "\n\n\n\n\n\n\n" | openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/nginx/ssl/magento.key -out /etc/nginx/ssl/magento.crt

COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN ["chmod", "+x", "/docker-entrypoint.sh"]
ENTRYPOINT ["/docker-entrypoint.sh"]

RUN mkdir ${MAGENTO_ROOT} && chown -R www:www ${MAGENTO_ROOT}

VOLUME ${MAGENTO_ROOT}

WORKDIR ${MAGENTO_ROOT}

CMD ["nginx", "-g", "daemon off;"]
