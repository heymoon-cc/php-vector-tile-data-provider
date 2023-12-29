FROM php:8.1-alpine3.16 AS builder
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    geos-dev \
    geos \
    git && \
    apk add --no-cache -X https://dl-cdn.alpinelinux.org/alpine/edge/community \
        php81-pecl-pcov
RUN git clone https://git.osgeo.org/gitea/geos/php-geos.git /usr/src/php/ext/geos && cd /usr/src/php/ext/geos && \
    	./autogen.sh && ./configure && make && \
    mv /usr/src/php/ext/geos/modules/geos.so /usr/local/lib/php/extensions/no-debug-non-zts-20210902/geos.so && \
    mv /usr/lib/php81/modules/pcov.so /usr/local/lib/php/extensions/no-debug-non-zts-20210902/pcov.so && \
    pecl install protobuf \
    && docker-php-ext-enable protobuf geos pcov
FROM php:8.1-alpine3.16 as runtime
RUN apk add --no-cache protoc geos
COPY --from=builder /usr/local/lib/php/extensions/no-debug-non-zts-20210902 /usr/local/lib/php/extensions/no-debug-non-zts-20210902
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d
RUN rm -rf /tmp/* /var/cache/apk/*
ENV PATH ./vendor/bin:/composer/vendor/bin:$PATH
ENV COMPOSER_HOME /composer
ENV COMPOSER_ALLOW_SUPERUSER 1
COPY --from=composer /usr/bin/composer /usr/bin/composer
WORKDIR /code
ENTRYPOINT ["/usr/bin/composer"]
