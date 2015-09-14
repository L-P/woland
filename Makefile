all: vendor

vendor: composer.lock composer.json
	composer install

.PHONY: server
server: vendor
	sensible-browser localhost:8082
	php -S localhost:8082 -t public public/index.php
