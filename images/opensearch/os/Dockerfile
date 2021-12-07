FROM opensearchproject/opensearch:1.1.0

RUN bin/opensearch-plugin install -b analysis-icu && \
    bin/opensearch-plugin install -b analysis-phonetic

ADD docker-healthcheck.sh /docker-healthcheck.sh
ADD docker-entrypoint.sh /docker-entrypoint.sh

HEALTHCHECK --retries=3 CMD ["bash", "/docker-healthcheck.sh"]

ENTRYPOINT ["/docker-entrypoint.sh"]

EXPOSE 9200 9300
