FROM docker.elastic.co/elasticsearch/elasticsearch:6.8.3

RUN echo "xpack.security.enabled: false" >> /usr/share/elasticsearch/config/elasticsearch.yml
RUN bin/elasticsearch-plugin install analysis-icu && \
    bin/elasticsearch-plugin install analysis-phonetic

EXPOSE 9200 9300
