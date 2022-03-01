FROM docker.elastic.co/elasticsearch/elasticsearch:{%version%}

RUN {%fix_repos%}yum -y install zip && \
    zip -q -d /usr/share/elasticsearch/lib/log4j-core-*.jar org/apache/logging/log4j/core/lookup/JndiLookup.class && \
    yum remove -y zip && \
    yum -y clean all && \
    rm -rf /var/cache

RUN echo "xpack.security.enabled: false" >> /usr/share/elasticsearch/config/elasticsearch.yml
{%single_node%}
RUN bin/elasticsearch-plugin install -b analysis-icu && \
    bin/elasticsearch-plugin install -b analysis-phonetic

ADD docker-healthcheck.sh /docker-healthcheck.sh
ADD docker-entrypoint.sh /docker-entrypoint.sh

HEALTHCHECK --retries=3 CMD ["bash", "/docker-healthcheck.sh"]

ENTRYPOINT ["/docker-entrypoint.sh"]

EXPOSE 9200 9300
