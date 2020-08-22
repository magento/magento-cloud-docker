FROM docker.elastic.co/elasticsearch/elasticsearch:{%version%}

RUN echo "xpack.security.enabled: false" >> /usr/share/elasticsearch/config/elasticsearch.yml
{%single_node%}
RUN bin/elasticsearch-plugin install -b analysis-icu && \
    bin/elasticsearch-plugin install -b analysis-phonetic

ADD docker-healthcheck.sh /docker-healthcheck.sh
ADD docker-entrypoint.sh /docker-entrypoint.sh

HEALTHCHECK --retries=3 CMD ["bash", "/docker-healthcheck.sh"]

ENTRYPOINT ["/docker-entrypoint.sh"]

EXPOSE 9200 9300
