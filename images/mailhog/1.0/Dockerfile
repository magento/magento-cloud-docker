#
# MailHog Dockerfile
#

FROM golang:1.24-alpine

# Install MailHog
RUN apk --no-cache add git \
  && go install github.com/mailhog/MailHog@v1.0.1 \
  && mv /go/bin/MailHog /usr/local/bin/MailHog \
  && apk del git

# Add mailhog user/group with uid/gid 1000.
# This is a workaround for boot2docker issue #581, see
# https://github.com/boot2docker/boot2docker/issues/581
RUN adduser -D -u 1000 mailhog

USER mailhog

WORKDIR /home/mailhog

ENTRYPOINT ["MailHog"]

# Expose the SMTP and HTTP ports:
EXPOSE 1025 8025
