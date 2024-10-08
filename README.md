# ringier-code-style

RingierSA PHP Code Style

## Installation

```bash
composer require ringierimu/ringier-code-style --dev
```

## Usage

### Fix code style

```bash
vendor/bin/ringier-code-style fix ...files...
```

### Create config files for styleci and IDE compatibility

```bash
vendor/bin/ringier-code-style config:dump --all
```

## Contriubutions

### Update dependencies

```bash
make update-dependencies
```

### Build fresh binary (required for each release)

Set the env `VERSION` to the next GitHub release version.

For example, if the current version is 0.6.30, then call:

```bash
make build VERSION=0.6.31
```
