---
phase: quick-261014-g6a
plan: 01
status: complete
subsystem: formatter
tags: [mago, php-cs-fixer, phar]
requires: []
provides: [mago-backed fix and fix:stdin, config:dump --mago]
tech-stack:
  added: [carthage-software/mago 1.57.0, revolt/event-loop v1.0.9]
  patterns: [RunsMago trait]
key-files:
  created: [mago.toml, app/Commands/Concerns/RunsMago.php]
  modified: [composer.json, composer.lock, app/Commands/FixCommand.php, app/Commands/FixStdinCommand.php, app/Commands/ConfigDumpCommand.php, box.json, .gitattributes, .phive/phars.xml, .github/workflows/release.yml]
  deleted: [.php-cs-fixer.php]
actuals:
  tasks: 3
  commits: 4
plan_head_before: 9e1af4037901c1dde4c16c96ae6527f2a161654e
plan_head_after: 0ec022e252e4d90c977de97d9418157c10fe5e4c
commits: 4
---

# Quick 261014-g6a: Replace php-cs-fixer with mago Summary

`fix` and `fix:stdin` now format with mago 1.57.0 (ritdu-sports rules, verbatim mago.toml) through a shared `RunsMago` trait that caches the binary per version in `${XDG_CACHE_HOME:-~/.cache}/ringier-code-style/mago`.

## Commits
- 5e73aef feat: mago-backed fix:stdin, RunsMago, dependency, mago.toml
- 64bdb0d feat: fix on mago, config:dump --mago (--php-cs-fixer deprecated no-op)
- 9c26aaa chore: box/gitattributes/phive/release.yml switched, .php-cs-fixer.php removed
- 0ec022e style: mago formatting of app, config, tests (layout only; second run is a no-op)

## Verified
- Dev mode: fix:stdin formats and echoes unparseable input unchanged; fix works with and without a project mago.toml (no mago.toml left in cwd); config:dump deprecated flags exit 0 and write nothing; --all writes mago.toml + .styleci.yml.
- PHAR (built, then `git checkout -- builds/ringier-code-style`): from an empty dir with cache cleared, downloaded mago into the cache, fix/fix:stdin worked, config:dump --php-cs-fixer silent, --all dumped an identical mago.toml.
- composer.lock changed only by adding carthage-software/mago 1.57.0 and revolt/event-loop v1.0.9.

## Deviations
- phive failed in composer's post-update script (`No executable gpg binary found`). Reran `composer install --no-scripts` and `mkdir -p tools`. composer-normalize was NOT installed locally (no gpg), so the composer.json normalize path in `fix` was not exercised and the PHAR was built without tools/composer-normalize. Rebuild with phive available before release.
- mago.toml `paths` line was NOT dropped: explicit paths / stdin never failed on the missing dirs.
- No Pest test added: vendor/bin/pest is not installed (Pest absent from composer.lock).
- Formatter converted `?string $input` to `string|null $input` in RunsMago (mago null-type-hint setting); intended.

## Known Stubs
None.

## Self-Check: PASSED
