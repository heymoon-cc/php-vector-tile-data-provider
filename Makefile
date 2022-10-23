protoc:
	protoc --proto_path=./proto --php_out=./proto/gen ./proto/vector_tile.proto

test:
	./vendor/bin/phpunit
