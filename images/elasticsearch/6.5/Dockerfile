FROM docker.elastic.co/elasticsearch/elasticsearch:6.5.4

RUN echo "xpack.security.enabled: false" >> /usr/share/elasticsearch/config/elasticsearch.yml
RUN bin/elasticsearch-plugin install analysis-icu && \
    bin/elasticsearch-plugin install analysis-phonetic

ADD docker-healthcheck.sh /docker-healthcheck.sh

HEALTHCHECK --retries=3 CMD ["bash", "/docker-healthcheck.sh"]

EXPOSE 9200 9300
