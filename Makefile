SHELL := /bin/bash

export UID := $(shell id -u)
export GID := $(shell id -g)

PHP := docker compose run --rm php

.PHONY: docker.build install update shell \
	code.fix code.check code.stan test test.coverage test.mutate ready

# ─────────────────────────────── Docker ───────────────────────────────
docker.build:
	docker compose build

shell:
	$(PHP) sh

# ────────────────────────────── Composer ──────────────────────────────
install:
	$(PHP) composer install

update:
	$(PHP) composer update

code.fix:
	$(PHP) composer code.fix

code.check:
	$(PHP) composer code.check

code.stan:
	$(PHP) composer code.stan

test:
	$(PHP) composer test

test.coverage:
	$(PHP) composer test.coverage

test.mutate:
	$(PHP) composer test.mutate

ready: code.fix code.check test.coverage
