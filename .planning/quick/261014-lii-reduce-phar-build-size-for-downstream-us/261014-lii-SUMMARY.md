---
phase: quick-261014-lii
plan: 01
status: complete
commits: 1
plan_head_before: 3757641
plan_head_after: 12d5b3721f756344b9334b5e3d0ba4a5402e4151
actuals:
  tasks: 1
  commits: 1
---

# Quick 261014-lii: Reduce PHAR size

Excluded five dead vendor dirs (laravel-zero/framework/bin, jolicode/jolinotif/{bin,doc,tools}, jolicode/php-os-helper/tools) in box.json `finder[0].exclude`.

PHAR bytes: before=7671893 after=4189185

Reduction: 3,482,708 bytes (45.4%).

Commit: 12d5b3721f756344b9334b5e3d0ba4a5402e4151 (box.json only). builds/ringier-code-style untouched; builds/rcs-verify removed.

Verify: full automated verify passed (exclude set, size drop, kept files present, five dirs absent, --version, config:dump vs stubs, fix:stdin, fix composer.json). fix/fix:stdin ran with no mago download or network failure observed.

## Deviations from Plan

None - plan executed exactly as written.

## Self-Check: PASSED
