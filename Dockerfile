FROM php:8.1-alpine3.16
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    geos-dev \
    git
RUN apk add --no-cache protoc geos
RUN pecl install protobuf \
    && docker-php-ext-enable protobuf \
	&& git clone https://git.osgeo.org/gitea/geos/php-geos.git /usr/src/php/ext/geos && cd /usr/src/php/ext/geos && \
	./autogen.sh && ./configure && make && \
	echo "extension=/usr/src/php/ext/geos/modules/geos.so" > /usr/local/etc/php/conf.d/docker-php-ext-geos.ini
RUN apk del -f .build-deps && rm -rf /tmp/* /var/cache/apk/*
ENV PATH ./vendor/bin:/composer/vendor/bin:$PATH
ENV COMPOSER_HOME /composer
ENV COMPOSER_ALLOW_SUPERUSER 1
COPY --from=composer /usr/bin/composer /usr/bin/composer
ADD composer.json /composer/
WORKDIR /composer
RUN composer install --no-scripts
ADD . /code
WORKDIR /code
RUN ln -s /composer/vendor /code/vendor && composer dump-autoload
ENTRYPOINT ["/code/vendor/bin/phpunit"]
