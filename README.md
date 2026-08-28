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
commits the result straight back to the PR branch. Drop the caller into your repository with:

```bash
vendor/bin/ringier-code-style config:dump --workflow
```

which writes:

```yaml
# .github/workflows/ringier-code-style.yml
name: ringier-code-style

on:
  pull_request:

concurrency:
  # Cancel superseded runs on rapid pushes so two runs never race to commit fixes.
  group: ${{ github.workflow }}-${{ github.head_ref }}
  cancel-in-progress: true

jobs:
  ringier-code-style:
    # The job lives in the fixer's own repo, so a fix to it reaches every consuming repo.
    # Pin to a release tag: https://github.com/RingierIMU/ringier-code-style/releases
    uses: RingierIMU/ringier-code-style/.github/workflows/fix-code-style.yml@main
    with:
      runs-on: ubicloud-standard-2-arm
    secrets:
      # Organisation secrets: the Client ID and private key of a GitHub App installed on
      # this repository with the Contents: read and write permission.
      app-client-id: ${{ secrets.PR_SIGNING_BOT_CLIENT_ID }}
      app-private-key: ${{ secrets.PR_SIGNING_BOT_PRIVATE_KEY }}
```

Both `with:` inputs are optional: `php-version` defaults to `8.3` and `runs-on` to
`ubuntu-latest`.

The two secrets are the Client ID and private key of a GitHub App installed on your repository
with the **Contents: read and write** permission. In the RingierIMU organisation they already exist
as the organisation secrets `PR_SIGNING_BOT_CLIENT_ID` and `PR_SIGNING_BOT_PRIVATE_KEY`; grant your
repository access to them rather than creating a second app.

A GitHub App is used rather than the default `GITHUB_TOKEN` for two reasons:

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

### Release

Run the **Release** workflow from the Actions tab
([`.github/workflows/release.yml`](.github/workflows/release.yml)). It builds the PHAR,
commits it, tags it and publishes the release with the binary attached.

- Leave **version** blank to bump the patch of the latest tag, or type an explicit
  `MAJOR.MINOR.PATCH` to release something else.
- Tick **dry-run** to build and verify without committing, tagging or publishing. The
  PHAR is uploaded as a run artifact so it can be inspected.

The workflow only runs from the default branch, refuses a version that is already tagged,
and fails if the PHAR it built does not report the right version or does not carry the
current `stubs/` content — the release commit is only made once those checks pass.

It needs the organisation secrets `PR_SIGNING_BOT_CLIENT_ID` and
`PR_SIGNING_BOT_PRIVATE_KEY` (the same GitHub App used by the code style workflow) so the
release commit lands **Verified**.

### Build a binary locally

Not needed to release — the workflow above does it. To build one for testing:

```bash
make build VERSION=0.6.31
```
