FROM php:8.5.12-fpm-alpine3.24 AS builder
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    git cmake && \
    apk add --no-cache -X https://dl-cdn.alpinelinux.org/alpine/edge/community \
        php85-pecl-pcov
RUN wget https://download.osgeo.org/geos/geos-3.13.1.tar.bz2 -O /usr/src/geos.tar.bz2 && cd /usr/src/ && \
    tar xvfj geos.tar.bz2 && rm -f geos.tar.bz2 && cd geos-3.13.1 && cmake \
      -DCMAKE_BUILD_TYPE=Release \
      -DCMAKE_INSTALL_PREFIX=/usr/local \
      -DBUILD_TESTING=ON && \
    make && make test && make install && \
    cp -r /usr/src/geos-3.13.1/include/geos* /usr/include/ && rm -rf /usr/src/geos-3.13.1
RUN git clone https://git.osgeo.org/gitea/geos/php-geos.git /usr/src/php/ext/geos && cd /usr/src/php/ext/geos && \
    ./autogen.sh && ./configure && make && \
    mv /usr/src/php/ext/geos/modules/geos.so /usr/local/lib/php/extensions/no-debug-non-zts-20250925/geos.so && \
    mv /usr/lib/php85/modules/pcov.so /usr/local/lib/php/extensions/no-debug-non-zts-20250925/pcov.so && \
    pecl install protobuf \
    && docker-php-ext-enable protobuf geos pcov
FROM php:8.5.12-fpm-alpine3.24 AS runtime
RUN apk add --no-cache protoc
COPY --from=builder /usr/local/bin/geosop /usr/local/bin/
COPY --from=builder /usr/local/lib/libgeos* /usr/local/lib/
COPY --from=builder /usr/local/lib/php/extensions/no-debug-non-zts-20250925 /usr/local/lib/php/extensions/no-debug-non-zts-20250925
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d
RUN rm -rf /tmp/* /var/cache/apk/*
ENV PATH=./vendor/bin:/composer/vendor/bin:$PATH
ENV COMPOSER_HOME=/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer /usr/bin/composer /usr/bin/composer
WORKDIR /code
ENTRYPOINT ["/usr/bin/composer"]
