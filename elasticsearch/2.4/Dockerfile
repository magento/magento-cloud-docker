FROM elasticsearch:2.4

RUN echo "xpack.security.enabled: false" >> /usr/share/elasticsearch/config/elasticsearch.yml
RUN bin/plugin install analysis-icu && \
    bin/plugin install analysis-phonetic

EXPOSE 9200 9300
