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

junit:
	docker run -v $$(pwd)/src:/code/src -v $$(pwd)/tests:/code/tests -v $$(pwd)/junit:/code/junit \
		heymoon/mvt-tools-tester --log-junit=/code/junit/junit.xml

clean:
	docker image rm php:8.1-alpine3.16 2> /dev/null || true  && \
	docker image rm composer 2> /dev/null || true && \
	docker image rm heymoon/mvt-tools-tester 2> /dev/null || true && \
	(rm -rf junit 2> /dev/null || sudo rm -rf junit)
