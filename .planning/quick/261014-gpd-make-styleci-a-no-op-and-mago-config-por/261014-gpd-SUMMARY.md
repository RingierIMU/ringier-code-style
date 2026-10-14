---
phase: quick-261014-gpd
plan: 01
status: incomplete
reason: Task 3 dropped by user (to be replanned); Task 1 idempotency gate fails on 6 files in property-core-backend (mago formatter non-idempotent, [formatter] untouchable per D-03)
commits: 1
plan_head_before: 8582af3
plan_head_after: 9ee1a88
---

# Quick 261014-gpd: portable mago.toml (StyleCI task dropped)

## Done

- Task 1 (mago.toml): committed 9ee1a88. php-version 8.3, `paths` removed, excludes = `**/*.blade.php`, `**/vendor/**`, `**/node_modules/**`, `storage`, `bootstrap/cache`; `[formatter]` byte-identical.
- Task 2 (ritdu-sports, ritdu-jobs): run, results below.
- Task 3 (StyleCI no-op + config:dump always-overwrite): **dropped by user, to be replanned**. `.styleci.yml` and `ConfigDumpCommand.php` untouched, nothing to revert.

## Per-repo results (fix run 3 times, shallow clones, current HEAD of each repo)

| Repo | Files changed | Parse errors | vendor/node_modules/storage/bootstrap-cache/blade changes | Sentinel | php -l | 8.4 new-in-member-access on added lines | Idempotent |
|------|---------------|--------------|-----|----------|--------|-----|------------|
| property-core-backend | 5328 | 0 | none | untouched | clean (only pre-existing Deprecated notices) | none | NO: pass 1 vs 2 differ in 6 files; pass 2 vs 3 identical |
| ritdu-sports | 577 | 0 | none | untouched | clean | none | yes |
| ritdu-jobs | 937 | 0 | none | untouched | clean | none | pass 1 vs 2 differ (33 diff lines); pass 2 vs 3 identical |

Gate output lines in the plan's format would be `property-core-backend: OK, 5328`, `ritdu-sports: OK, 577`, `ritdu-jobs: OK, 937` except for the idempotency failure noted below.

## Issue: mago format is not idempotent on first re-run

Second `fix` run changes a handful of files (6 in property-core-backend, a few in ritdu-jobs), then stabilises on the third run. Typical hunks are `??` / `&&` / `.` chain continuation indentation and `$url = config(...) . route(...)` wrapping, all under `preserve-breaking-*` options in `[formatter]`. Examples in property-core-backend: `app/Actions/Api/V3/Listing/ListingActionFormat.php`, `app/Http/Resources/Api/V3/Listing/ListingResource.php`, `app/Notifications/Sudonum/AgentNonResponseReminderNotification.php`, `app/Policies/CRM/IMO/DeveloperContactPolicy.php`, `app/Subscriber/Imo/ProjectWorkflowSubscriber.php`. Per plan D-03 I did not touch `[formatter]`. This is a mago formatter property, not caused by the excludes/php-version change. Needs a decision (run fix twice, or retune `preserve-breaking-*`).

## Deviations

- `composer install --no-scripts` run (vendor was empty); composer.lock unchanged.
- The plan's verify command deletes clones; instead I ran the same checks manually and KEPT the clones (coordinator instruction). The plan's "new-in-member-access" grep (`new X(...)->`) has false positives on nested parens (e.g. `dispatch(new Job($a->b())->...`-shaped text); I used a stricter no-nested-parens regex, which found none.
- Committed on `main` (protected-branch assertion overridden per orchestrator instruction). Not pushed.
- Did not run the final-state ROADMAP/STATE updates (no STATE.md).

## Clones kept (formatted by new mago.toml after 3 passes, uncommitted, untracked sentinel at bootstrap/cache/zz-sentinel.php)

- /tmp/gpd-clones.D8uwK2/property-core-backend
- /tmp/gpd-clones.D8uwK2/ritdu-sports
- /tmp/gpd-clones.D8uwK2/ritdu-jobs

(Also in that dir: *.log, *.d1/d2/d3 diffs, d1.patch/d2.patch/d3.patch, a stray `pcb-snap-unused` copy of property-core-backend.)

## Self-Check: PASSED (commit 9ee1a88 exists; mago.toml static checks passed)

## Orchestrator addendum (2026-10-14)

- Task 3 was replaced at the user's request. StyleCI is removed entirely, not made a no-op (`4b9f60c`).
- 1.0 breaking changes were added (`c7a17b4`): the deprecated `config:dump` flags and the ignored PHP CS Fixer options are removed, and `fix` now exits non-zero on failure.
- Idempotency root cause: `preserve-breaking-argument-list` and `preserve-breaking-binary-expression` (mago bug, filed upstream as carthage-software/mago#2524). With both off, a second pass changes 0 files on all three repos. The fix (two-pass `fix`, or both options off) is still pending a user decision.
