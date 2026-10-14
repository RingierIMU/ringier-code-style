---
phase: quick-261014-ib8
plan: 01
subsystem: build
tags: [composer-normalize, phive-removal, phar]
requires: []
provides:
  - composer-normalize bundled from vendor/ and run in-process from the PHAR
affects: [app/Commands/FixCommand.php, box.json, composer.json, release.yml]
tech-stack:
  added: [ergebnis/composer-normalize 2.54.0, composer/composer 2.10.3]
  patterns: [Composer Application hosting an in-process command, COMPOSER env to defeat box intercept]
key-files:
  modified:
    - composer.json
    - composer.lock
    - app/Commands/FixCommand.php
    - box.json
    - Makefile
    - .github/workflows/release.yml
    - .gitattributes
    - .gitignore
  deleted:
    - .phive/phars.xml
decisions:
  - Schema path gets a file:// prefix outside the PHAR (phar:// paths already carry a scheme)
metrics:
  tasks: 2
  commits: 3
  completed: 2026-10-14
status: complete
actuals:
  tasks: 2
  commits: 3
plan_head_before: ae3c3e08658d99c8b5cf745542303c2d0e7ea9b9
plan_head_after: 89b0f64
---

# Phase quick-261014-ib8 Plan 01: Remove phive, add composer-normalize Summary

composer-normalize now ships as a locked composer dev dependency and runs in-process inside the PHAR via a Composer Application, with phive gone from the repo.

## Commits

- 8f30e94 feat: run composer-normalize from vendor in-process (Task 1)
- 73dfe3c fix: resolve the normalize schema outside the PHAR (also carries the .phive/phars.xml deletion, see Deviations)
- 89b0f64 chore: remove phive (Task 2)

## Facts

- Locked versions: ergebnis/composer-normalize 2.54.0, composer/composer 2.10.3, symfony/console v7.4.17 (unchanged).
- `composer normalize` on this repo only removed the empty `"keywords": []`. That is not a reorder, so per the plan it was reverted (key kept, lock re-hashed with `composer update --lock`).
- PHAR size: 8,151,356 bytes before (tracked builds/ringier-code-style), about 18.1 MB after (builds/rcs-verify, deleted afterwards; the tracked PHAR is untouched).
- Deprecation Notice lines in fix output: none. Only the scratch-project warning "Composer could not detect the root package version" appeared.
- Dependency check: both packages were 2.54.0 / 2.10.3 on Packagist at execution time; no resolution conflict.

## Verification

- Task 1 verify: PASS (scripts only fix-code-style, allow-plugins entry, require-dev entries, no tools in box.json, mago fmt --check clean, PHAR has no root tools/, scratch `fix` normalizes + keeps lock valid + "already normalized" on rerun, exit 0).
- Task 2 verify: PASS (no .phive/tools, `git grep -i phive` outside .planning empty, `composer install` with a failing phive stub first on PATH, PHAR `fix composer.json` with no lock created, `fix sub/composer.json` re-locks sub/ with no cwd composer.json, invalid composer.json exits non-zero, dev entrypoint no-path `fix` normalizes and re-locks).
- Both verifies were rerun in full after the schema fix below; builds/ringier-code-style and git status show no builds changes.

## Deviations from Plan

**1. [Rule 1 - Bug] Dev entrypoint could not load the schema**
- **Found during:** Task 2 verify (log showed `Schema URI ... does not reference a document that could be read` while the verify still reported pass; the dev-run file ordering was not actually asserted strictly enough in that run, so I reproduced it by hand).
- **Issue:** justinrainbow/json-schema 6.13.1 resolves a bare absolute path to `:///...`; only the PHAR's `phar://` path worked, so `php ringier-code-style fix` outside the PHAR failed to normalize.
- **Fix:** `Phar::running() === '' ? 'file://' . $schema : $schema` in FixCommand::runComposerNormalize.
- **Files modified:** app/Commands/FixCommand.php
- **Commit:** 73dfe3c

**2. [Process] Staged .phive deletion swept into the fix commit**
- `git rm -r .phive` was already staged when the schema-fix commit ran, so `.phive/phars.xml` was deleted in 73dfe3c instead of 89b0f64. Local, unpushed; history not rewritten.

**3. [Environment] Concurrent session on the same tree**
- The branch was `1.x`, not `main`, and another session committed a8559c9 and ae3c3e0 (and briefly held uncommitted edits to composer.json, config/, bootstrap/) while this plan ran. Waited for a clean tree, then proceeded; only plan files were staged.

## Known Stubs

None.

## Threat Flags

None.

## Self-Check: PASSED
