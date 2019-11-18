FROM debian:jessie
ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update \
    && apt-get install -y pound \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists \
    && mkdir -p /var/run/pound \
    && chown -R www-data:www-data /var/run/pound

VOLUME /cert.pem

EXPOSE 443

COPY certs/magento2.docker.pem /certs/cert.pem
COPY pound.cfg /etc/pound/
COPY entrypoint.sh /entrypoint.sh

ENV HTTPS_UPSTREAM_SERVER_ADDRESS   varnish
ENV HTTPS_UPSTREAM_SERVER_PORT  80
ENV CERT_PATH   /certs/cert.pem
ENV TIMEOUT 300
ENV REWRITE_LOCATION 0

RUN ["chmod", "+x", "/entrypoint.sh"]

ENTRYPOINT ["/entrypoint.sh"]
