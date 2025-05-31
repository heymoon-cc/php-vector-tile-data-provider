protoc:
	protoc --proto_path=./proto --php_out=./proto/gen ./proto/vector_tile.proto

cache:
	docker pull heymoon/php-vector-tile-data-provider-tester-builder || true
	docker pull heymoon/php-vector-tile-data-provider-tester || true

image: cache
	docker build --cache-from=heymoon/php-vector-tile-data-provider-tester-builder --target builder \
		-t heymoon/php-vector-tile-data-provider-tester-builder tests/runtime
	docker build --cache-from=heymoon/php-vector-tile-data-provider-tester-builder \
 		--cache-from=heymoon/php-vector-tile-data-provider-tester --target runtime \
		-t heymoon/php-vector-tile-data-provider-tester tests/runtime

push: image
	docker push heymoon/php-vector-tile-data-provider-tester-builder
	docker push heymoon/php-vector-tile-data-provider-tester

composer: clean.container
	docker run --name php-vector-tile-data-provider-tester -v $$(pwd):/code heymoon/php-vector-tile-data-provider-tester install

test: clean.container
	docker run --name php-vector-tile-data-provider-tester -v $$(pwd):/code heymoon/php-vector-tile-data-provider-tester test

coverage: clean.container
	docker run --name php-vector-tile-data-provider-tester -v $$(pwd):/code heymoon/php-vector-tile-data-provider-tester coverage

phpmd: clean.container
	docker run --name php-vector-tile-data-provider-tester -v $$(pwd):/code heymoon/php-vector-tile-data-provider-tester phpmd

clean.container:
	docker rm php-vector-tile-data-provider-tester 2> /dev/null || true

clean: clean.container
	docker image rm php:8.1-alpine3.16 2> /dev/null || true  && \
	docker image rm composer 2> /dev/null || true && \
	docker image rm heymoon/php-vector-tile-data-provider-tester-builder 2> /dev/null || true && \
	docker image rm heymoon/php-vector-tile-data-provider-tester 2> /dev/null || true && \
	(rm -rf "test-reports" 2> /dev/null || sudo rm -rf "test-reports" || true) && \
	(rm -rf vendor 2> /dev/null || sudo rm -rf vendor || true) && \
	(rm -rf .phpunit.cache 2> /dev/null || sudo rm -rf .phpunit.cache 2> /dev/null || true) && \
	(rm -rf composer.lock 2> /dev/null || sudo rm -rf composer.lock 2> /dev/null || true) && \
	(rm -rf proto/gen/Vector_tile 2> /dev/null || sudo rm -rf proto/gen/Vector_tile 2> /dev/null || true) && \
	(rm -rf proto/gen/GPBMetadata 2> /dev/null || sudo rm -rf proto/gen/GPBMetadata 2> /dev/null || true)