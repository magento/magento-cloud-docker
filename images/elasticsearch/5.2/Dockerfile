FROM elasticsearch:5.2.2

RUN apt update || true \
 && apt install zip
RUN zip -q -d /usr/share/elasticsearch/lib/log4j-core-*.jar org/apache/logging/log4j/core/lookup/JndiLookup.class

RUN bin/elasticsearch-plugin install analysis-icu && \
    bin/elasticsearch-plugin install analysis-phonetic

ADD docker-healthcheck.sh /docker-healthcheck.sh

ADD docker-entrypoint.sh /docker-entrypoint.sh

HEALTHCHECK --retries=3 CMD ["bash", "/docker-healthcheck.sh"]

EXPOSE 9200 9300
