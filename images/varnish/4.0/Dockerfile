FROM centos:centos7

RUN yum update -y && \
    yum install -y epel-release && \
    yum install -y varnish && \
    yum install -y libmhash-devel && \
    yum clean all

EXPOSE 80

ADD entrypoint.sh /entrypoint.sh

ENV VCL_CONFIG /data/varnish.vcl
ENV CACHE_SIZE 64m
ENV VARNISHD_PARAMS -p default_ttl=3600 -p default_grace=3600 -p feature=+esi_ignore_https -p feature=+esi_disable_xml_check

COPY etc/varnish.vcl /data/varnish.vcl

RUN ["chmod", "644", "/data/varnish.vcl"]
RUN ["chmod", "+x", "/entrypoint.sh"]

ENTRYPOINT ["/entrypoint.sh"]
