SHELL := /bin/bash

VERSION ?= latest

default:

update-dependencies:
	composer update --with-all-dependencies
	phive update --force-accept-unsigned
	composer outdated --direct
	phive outdated

build:
	./ringier-code-style app:build ringier-code-style --build-version="$(VERSION)"
