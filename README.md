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

### Fix code style automatically on every pull request

`fix-code-style.yml` is a reusable workflow. It fixes the PHP files a pull request touches and
commits the result straight back to the PR branch:

```yaml
# .github/workflows/ringier-code-style.yml
name: ringier-code-style

on:
  pull_request:

jobs:
  fix-code-style:
    # Pin to a release tag: https://github.com/RingierIMU/ringier-code-style/releases
    uses: RingierIMU/ringier-code-style/.github/workflows/fix-code-style.yml@<tag>
    with:
      php-version: '8.3'      # optional, defaults to 8.3
      runs-on: ubuntu-latest  # optional, defaults to ubuntu-latest
    secrets:
      app-client-id: ${{ secrets.STYLE_BOT_CLIENT_ID }}
      app-private-key: ${{ secrets.STYLE_BOT_PRIVATE_KEY }}
```

The two secrets are the Client ID and private key of a GitHub App installed on your repository
with the **Contents: read and write** permission. A GitHub App is used rather than the default
`GITHUB_TOKEN` for two reasons:

- Commits made with `GITHUB_TOKEN` do not trigger further workflow runs, so the pull request's
  required checks would never report against the style commit and the PR would stay blocked.
- The commit is created through GitHub's GraphQL API, which signs it with GitHub's own key. It
  therefore shows as **Verified** and is accepted on branches that require signed commits.

Pull requests opened from forks are skipped, because forks receive no secrets.

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
