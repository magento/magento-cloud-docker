FROM mnuessler/tls-termination-proxy

EXPOSE 443

COPY certs/magento2.docker.pem /certs/cert.pem
COPY pound.cfg /etc/pound/

ENV HTTPS_UPSTREAM_SERVER_ADDRESS   varnish
ENV HTTPS_UPSTREAM_SERVER_PORT  80
ENV CERT_PATH   /certs/cert.pem
ENV TIMEOUT 15
ENV REWRITE_LOCATION 0
