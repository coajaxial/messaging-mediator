image = coajaxial/messaging-mediator
prefix = docker run --rm -i -v $(shell pwd):/opt/project -u 1000 $(image)

.PHONY: image
image:
	docker build -t coajaxial/messaging-mediator - < Dockerfile

.PHONY: install
install: image
	$(prefix) composer install

.PHONY: update
update: install
	$(prefix) composer update

.PHONY: unit-test
unit-test: install
	$(prefix) vendor/bin/phpunit --testsuite unit

.PHONY: integration-test
integration-test: install
	$(prefix) vendor/bin/phpunit --testsuite integration

.PHONY: psalm
psalm: install
	$(prefix) vendor/bin/psalm --no-progress --show-info=true --output-format=text --threads=$(shell nproc --all)