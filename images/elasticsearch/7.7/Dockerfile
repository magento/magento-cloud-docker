FROM docker.elastic.co/elasticsearch/elasticsearch:7.7.1

RUN echo "xpack.security.enabled: false" >> /usr/share/elasticsearch/config/elasticsearch.yml
RUN echo "discovery.type: single-node" >> /usr/share/elasticsearch/config/elasticsearch.yml
RUN bin/elasticsearch-plugin install -b analysis-icu && \
    bin/elasticsearch-plugin install -b analysis-phonetic

ADD docker-healthcheck.sh /docker-healthcheck.sh
ADD docker-entrypoint.sh /docker-entrypoint.sh

HEALTHCHECK --retries=3 CMD ["bash", "/docker-healthcheck.sh"]

ENTRYPOINT ["/docker-entrypoint.sh"]

EXPOSE 9200 9300
