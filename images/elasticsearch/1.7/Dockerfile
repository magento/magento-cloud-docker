FROM elasticsearch:1.7

RUN echo "xpack.security.enabled: false" >> /usr/share/elasticsearch/config/elasticsearch.yml
RUN plugin --install elasticsearch/elasticsearch-analysis-icu/2.7.0 && \
    plugin --install elasticsearch/elasticsearch-analysis-phonetic/2.7.0

ADD docker-healthcheck.sh /docker-healthcheck.sh

HEALTHCHECK --retries=3 CMD ["bash", "/docker-healthcheck.sh"]

EXPOSE 9200 9300
