SHELL := /bin/bash

VERSION ?= latest

default:

update-dependencies:
	composer update --with-all-dependencies
	composer outdated --direct

build:
	./ringier-code-style app:build ringier-code-style --build-version="$(VERSION)"
