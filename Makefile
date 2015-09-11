all: vendor

vendor: composer.lock composer.json
	composer install
