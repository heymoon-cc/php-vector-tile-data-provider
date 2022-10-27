protoc:
	protoc --proto_path=./proto --php_out=./proto/gen ./proto/vector_tile.proto

test:
	./vendor/bin/phpunit

cache:
	docker pull heymoon/mvt-tools-tester || true

image: cache
	docker build --cache-from=heymoon/mvt-tools-tester -t heymoon/mvt-tools-tester .

push: image
	docker push heymoon/mvt-tools-tester

composer:
	docker run -v $$(pwd):/code heymoon/mvt-tools-tester install

audit:
	docker run -v $$(pwd):/code heymoon/mvt-tools-tester test

phpmd:
	docker run -v $$(pwd):/code heymoon/mvt-tools-tester phpmd

clean:
	docker image rm php:8.1-alpine3.16 2> /dev/null || true  && \
	docker image rm composer 2> /dev/null || true && \
	docker image rm heymoon/mvt-tools-tester 2> /dev/null || true && \
	(rm -rf "test-reports" 2> /dev/null || sudo rm -rf "test-reports" || true) && \
	(rm -rf vendor 2> /dev/null || sudo rm -rf vendor || true) && \
	(rm -rf .phpunit.cache 2> /dev/null || sudo rm -rf .phpunit.cache 2> /dev/null || true) && \
	(rm -rf composer.lock 2> /dev/null || sudo rm -rf composer.lock 2> /dev/null || true) && \
	(rm -rf proto/gen/Vector_tile 2> /dev/null || sudo rm -rf proto/gen/Vector_tile 2> /dev/null || true) && \
	(rm -rf proto/gen/GPBMetadata 2> /dev/null || sudo rm -rf proto/gen/GPBMetadata 2> /dev/null || true)

