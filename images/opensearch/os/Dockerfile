FROM opensearchproject/opensearch:{%version%}

USER root
RUN yum -y install zip && \
    zip -q -d /usr/share/opensearch/lib/log4j-core-*.jar org/apache/logging/log4j/core/lookup/JndiLookup.class && \
    yum remove -y zip && \
    yum -y clean all && \
    rm -rf /var/cache
USER opensearch

RUN bin/opensearch-plugin install -b analysis-icu && \
    bin/opensearch-plugin install -b analysis-phonetic

ADD docker-healthcheck.sh /docker-healthcheck.sh
ADD docker-entrypoint.sh /docker-entrypoint.sh

HEALTHCHECK --retries=3 CMD ["bash", "/docker-healthcheck.sh"]

ENTRYPOINT ["/docker-entrypoint.sh"]

EXPOSE 9200 9300
