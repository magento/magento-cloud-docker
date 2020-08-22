FROM elasticsearch:2.4

RUN apt update -y && \
    apt install procps -y

RUN echo "xpack.security.enabled: false" >> /usr/share/elasticsearch/config/elasticsearch.yml
RUN bin/plugin install -b analysis-icu && \
    bin/plugin install -b analysis-phonetic

ADD docker-healthcheck.sh /docker-healthcheck.sh

HEALTHCHECK --retries=3 CMD ["bash", "/docker-healthcheck.sh"]

COPY docker-entrypoint.sh /docker-entrypoint.sh
ENTRYPOINT ["/docker-entrypoint.sh"]

EXPOSE 9200 9300
