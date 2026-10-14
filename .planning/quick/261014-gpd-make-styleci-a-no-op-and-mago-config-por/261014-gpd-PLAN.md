---
phase: quick-261014-gpd
plan: 01
type: execute
wave: 1
depends_on: []
files_modified:
  - mago.toml
  - .styleci.yml
  - app/Commands/ConfigDumpCommand.php
autonomous: true
requirements:
  - QUICK-261014-gpd-1
  - QUICK-261014-gpd-2

estimate:
  tokens: 45000
  raw_tokens: 45000
  tasks: 3
  confidence: low

must_haves:
  truths:
    - "With the bundled .styleci.yml, StyleCI applies no fixers (php preset none), so it cannot revert anything mago formats (D-01)"
    - "`config:dump --styleci` and `config:dump --all` overwrite an existing .styleci.yml without `--force`; mago.toml and the workflow stub still need `--force` (D-02)"
    - "In shallow clones of property-core-backend, ritdu-sports and ritdu-jobs, `ringier-code-style fix <their fix-code-style paths>` with the bundled mago.toml exits 0 with no mago parse errors (D-03, D-04a)"
    - "No file under any vendor/, node_modules/, storage/ or bootstrap/cache/ dir and no *.blade.php changes, even when those dirs are passed as explicit paths (D-03, D-04b)"
    - "Every file the fix changes passes `php -l`, a second fix run changes nothing, and no added line uses PHP 8.4-only unparenthesised new-in-member-access (D-03, D-04c/d)"
  artifacts:
    - path: mago.toml
      provides: "Portable bundled formatter config: php-version 8.3, no paths list, generic excludes, [formatter] unchanged"
    - path: .styleci.yml
      provides: "No-op StyleCI config: php preset none"
    - path: app/Commands/ConfigDumpCommand.php
      provides: "config:dump that always overwrites .styleci.yml"
  key_links:
    - from: app/Commands/Concerns/RunsMago.php
      to: mago.toml
      via: "base_path('mago.toml') temp copy, used when the cwd has no non-empty mago.toml (true for all three consumers)"
    - from: app/Commands/ConfigDumpCommand.php
      to: .styleci.yml
      via: "styleci branch calls exportFiles with force true"
    - from: "consumer composer post-install/post-update `config:dump --styleci --quiet`"
      to: .styleci.yml
      via: "always-overwrite delivers the no-op config without consumers adding --force"
---

<objective>
Make StyleCI a no-op so it never reverts mago output (requirement 1), and make the bundled mago.toml correct for every consumer project's PHP version and folder layout (requirement 2), proven by running `fix` in real clones of the three consumers.

Purpose: consumers get the bundled mago.toml (none has its own) and dump .styleci.yml without `--force`, so both bundled files must be right for all of them.
Output: edited mago.toml, .styleci.yml and app/Commands/ConfigDumpCommand.php, plus per-repo verification results in the SUMMARY.
</objective>

<execution_context>
@~/.claude/gsd-core/workflows/execute-plan.md
@~/.claude/gsd-core/templates/summary.md
</execution_context>

<context>
@.planning/quick/261014-g6a-replace-php-cs-fixer-with-mago/261014-g6a-SUMMARY.md
@mago.toml
@.styleci.yml
@app/Commands/ConfigDumpCommand.php
@app/Commands/Concerns/RunsMago.php
@app/Commands/FixCommand.php

Execution rules (locked, D-05): run sequentially on the main working tree. Local commits on main are fine. No push, no PR, no branch. Do not rebuild or commit builds/ringier-code-style. Change nothing in the consumer repos: never run git add, commit or push in a clone. Clones live only in a `mktemp -d` dir outside this repo and are deleted by the verify command. No new abstractions.

Facts verified by the orchestrator on 2026-10-14. Do not re-research them.
- Consumer fix-code-style paths:
  - property-core-backend (PHP ^8.3, platform 8.4.21): app config database bootstrap/app.php nova-components public routes lang resources/views tests
  - ritdu-sports (PHP ^8.4): app bootstrap config database lang public resources routes tests
  - ritdu-jobs (PHP ^8.3): app doc database bootstrap public routes resources lang templates
- None of the consumers has a mago.toml or a customised .styleci.yml. All three run `config:dump --styleci --quiet` without `--force` from composer post-install and post-update.
- mago 1.57.0 does not read the PHP version from composer.json. The formatter output depends on php-version only for trailing commas (PHP 8.0 features) and new-without-parentheses (PHP 8.4), and `parentheses-around-new-in-member-access = true` already forces the parens. The parser is not version-gated.
- The planner checked FixCommand: it always exits 0, even when mago fails. It prints mago's stderr (for example `ERROR Failed to parse file ...`) to stdout through `$this->error`. So the verify gates grep the combined log for parse errors rather than trusting the exit code.
- The planner ran the current HEAD mago.toml against a property-core-backend clone. It changes `lang/vendor/nova/...` and `lang/vendor/cookie-consent/...` files, so the vendor gate trips today. That confirms the bug the new excludes fix. Expect thousands of changed files per repo, because of the php-cs-fixer to mago switch.
- This repo's vendor/ dir is empty right now. The dev entrypoint `php ringier-code-style` needs vendor/autoload.php.
</context>

<tasks>

<task type="tracer">
  <name>Task 1: Portable mago.toml, proven end-to-end on property-core-backend</name>
  <files>mago.toml</files>
  <precondition>`gh auth status` succeeds with read access to RingierIMU private repos, and vendor/autoload.php exists (if not, run `composer install --no-scripts`; the post-install phive script fails without gpg, see the 261014-g6a SUMMARY. `git diff --quiet composer.lock` must still pass afterwards).</precondition>
  <action>
Edit mago.toml at the repo root, per D-03:
1. Replace the first line with two lines. First the comment `# Lowest PHP the consumer projects run. Formatter output is only version-gated on trailing commas (PHP 8.0 features) and new-without-parentheses (forced off below), so it stays valid on 8.3+.` Then `php-version = "8.3"`.
2. In the `[source]` table, delete the line that lists the ritdu-sports directories. Explicit paths passed to `fix` override it anyway.
3. Replace the ritdu-sports-specific Blade comment in `[source]` with the generic comment `# Never format Blade templates, dependencies or generated files, even when passed as explicit paths.`
4. Set the excludes line to exactly `excludes = ["**/*.blade.php", "**/vendor/**", "**/node_modules/**", "storage", "bootstrap/cache"]`. After this edit, `[source]` holds only that comment and the excludes line.
5. Leave everything from the `# Tuned to stay as close as possible...` comment line to the end of the file byte-for-byte unchanged. That covers the whole `[formatter]` table.

Then run the verify command from the repo root, with a Bash timeout of 600000 ms. Its `check` function shallow-clones the consumer into a `mktemp -d` dir. It plants an untracked, unformatted sentinel PHP file in `bootstrap/cache/`, which proves the plain-dir excludes still resolve against the project root when `fix` hands mago a temp config copy. Then it runs this repo's dev entrypoint `fix` with the consumer's paths and asserts D-04 (a) to (d), plus the 8.4-syntax grep on added lines. It deletes the clones on exit.

On failure:
- A path under an excluded dir changed, or the sentinel changed: fix the excludes glob in mago.toml and rerun.
- A parse error: run `git -C <clone> show HEAD:<file> | php -l`. If the HEAD file is already invalid PHP, record it in the SUMMARY as pre-existing. Do not add repo-specific excludes, because the bundled config stays generic per D-03. If the HEAD file is valid PHP but mago cannot parse it, stop and report.
- A non-idempotent second run, or an unparenthesised new-in-member-access on an added line: stop and report the file and the diff hunk. Do NOT touch `[formatter]` (D-03).

Commit only mago.toml: `fix(quick-261014-gpd): make bundled mago.toml portable across consumer projects`.
  </action>
  <verify>
    <automated>grep -qxF 'php-version = "8.3"' mago.toml && grep -qxF 'excludes = ["**/*.blade.php", "**/vendor/**", "**/node_modules/**", "storage", "bootstrap/cache"]' mago.toml && grep -qxF '# Never format Blade templates, dependencies or generated files, even when passed as explicit paths.' mago.toml && [ "$(sed -n '/^\[source\]/,/^\[formatter\]/p' mago.toml | grep -vE '^[[:space:]]*(#|\[|$)' | cut -d' ' -f1)" = excludes ] && [ "$(sed -n '/^# Tuned/,$p' mago.toml | shasum)" = "$(git show 8582af3:mago.toml | sed -n '/^# Tuned/,$p' | shasum)" ] && ( set -uo pipefail; R=$PWD; T=$(mktemp -d); rc=0; printf '\074?php $a=1;\n' > "$T/sentinel.ref"; check() { n=$1; shift; C="$T/$n"; gh repo clone "RingierIMU/$n" "$C" -- --depth 1 -q || return 1; mkdir -p "$C/bootstrap/cache" && cp "$T/sentinel.ref" "$C/bootstrap/cache/zz-sentinel.php"; (cd "$C" && php "$R/ringier-code-style" fix "$@") > "$T/$n.log" 2>&1 || { cat "$T/$n.log"; return 1; }; if grep -E 'ERROR|Failed to parse' "$T/$n.log"; then return 1; fi; s=$(git -C "$C" status --porcelain -uno) || return 1; if printf '%s\n' "$s" | grep -E '(^|[ /])(vendor|node_modules|storage|bootstrap/cache)/|\.blade\.php$'; then return 1; fi; cmp "$C/bootstrap/cache/zz-sentinel.php" "$T/sentinel.ref" || return 1; git -C "$C" diff --name-only -z | xargs -0 -P8 -I{} php -l "$C/{}" > /dev/null || return 1; h=$(git -C "$C" diff | shasum); (cd "$C" && php "$R/ringier-code-style" fix "$@") > /dev/null 2>&1; [ "$h" = "$(git -C "$C" diff | shasum)" ] || { echo "$n: second fix run changed files"; return 1; }; a=$(git -C "$C" diff -U0) || return 1; if printf '%s\n' "$a" | grep -E '^\+[^+]' | grep -E 'new [A-Za-z\\]+\([^)]*\)->'; then return 1; fi; echo "$n: OK, $(git -C "$C" diff --name-only | wc -l | tr -d ' ') files changed"; }; check property-core-backend app config database bootstrap/app.php nova-components public routes lang resources/views tests || rc=1; rm -rf "$T"; exit $rc )</automated>
  </verify>
  <done>The static mago.toml checks pass. The command prints `property-core-backend: OK, N files changed` and exits 0: no parse errors, no vendor/node_modules/storage/bootstrap-cache/blade changes, sentinel untouched, php -l clean, idempotent, and no 8.4-only new syntax. mago.toml is committed and the clone dir is deleted.</done>
</task>

<task type="auto">
  <name>Task 2: Prove the same mago.toml on ritdu-sports (PHP 8.4) and ritdu-jobs (PHP 8.3)</name>
  <files>none in this repo (scratch clones under mktemp -d only; results go in the SUMMARY)</files>
  <action>
Run the verify command from the repo root with a Bash timeout of 600000 ms. It uses the same `check` function as Task 1, applied to ritdu-sports and ritdu-jobs with their fix-code-style paths from the context table. That covers D-04 for the other two consumers. The new-in-member-access grep on added lines is the PHP 8.3 safety gate for ritdu-jobs (D-04). It runs for both repos because the check costs nothing.

Handle failures exactly as in Task 1. If an excludes glob needs a fix, edit mago.toml, rerun BOTH the Task 1 and Task 2 verify commands, and commit the change as `fix(quick-261014-gpd): widen mago.toml excludes for <repo> layout`.

Copy the three `<repo>: OK, N files changed` lines (Task 1 plus this task) into the SUMMARY.
  </action>
  <verify>
    <automated>( set -uo pipefail; R=$PWD; T=$(mktemp -d); rc=0; printf '\074?php $a=1;\n' > "$T/sentinel.ref"; check() { n=$1; shift; C="$T/$n"; gh repo clone "RingierIMU/$n" "$C" -- --depth 1 -q || return 1; mkdir -p "$C/bootstrap/cache" && cp "$T/sentinel.ref" "$C/bootstrap/cache/zz-sentinel.php"; (cd "$C" && php "$R/ringier-code-style" fix "$@") > "$T/$n.log" 2>&1 || { cat "$T/$n.log"; return 1; }; if grep -E 'ERROR|Failed to parse' "$T/$n.log"; then return 1; fi; s=$(git -C "$C" status --porcelain -uno) || return 1; if printf '%s\n' "$s" | grep -E '(^|[ /])(vendor|node_modules|storage|bootstrap/cache)/|\.blade\.php$'; then return 1; fi; cmp "$C/bootstrap/cache/zz-sentinel.php" "$T/sentinel.ref" || return 1; git -C "$C" diff --name-only -z | xargs -0 -P8 -I{} php -l "$C/{}" > /dev/null || return 1; h=$(git -C "$C" diff | shasum); (cd "$C" && php "$R/ringier-code-style" fix "$@") > /dev/null 2>&1; [ "$h" = "$(git -C "$C" diff | shasum)" ] || { echo "$n: second fix run changed files"; return 1; }; a=$(git -C "$C" diff -U0) || return 1; if printf '%s\n' "$a" | grep -E '^\+[^+]' | grep -E 'new [A-Za-z\\]+\([^)]*\)->'; then return 1; fi; echo "$n: OK, $(git -C "$C" diff --name-only | wc -l | tr -d ' ') files changed"; }; check ritdu-sports app bootstrap config database lang public resources routes tests || rc=1; check ritdu-jobs app doc database bootstrap public routes resources lang templates || rc=1; rm -rf "$T"; exit $rc )</automated>
  </verify>
  <done>The command prints `ritdu-sports: OK, N files changed` and `ritdu-jobs: OK, N files changed` and exits 0. The scratch clones are deleted. The SUMMARY records all three per-repo changed-file counts.</done>
</task>

<task type="auto">
  <name>Task 3: No-op .styleci.yml that config:dump always overwrites</name>
  <files>.styleci.yml, app/Commands/ConfigDumpCommand.php</files>
  <action>
1. Per D-01, replace the whole of .styleci.yml with exactly three lines:
   - the comment `# mago (mago.toml) owns PHP formatting, so StyleCI must not change anything.`
   - `php:`
   - `preset: none`, indented two spaces.
   The current preset, risky flag, fixer list and finder block all go. With no fixers they are moot.
2. Per D-02, in app/Commands/ConfigDumpCommand.php `handle()`, change the styleci branch (the one taken for `--styleci` or `--all`) so it passes `true` instead of `(bool) $this->option('force')` as the second argument to `exportFiles`. Put this one-line comment directly above that call: `// Always overwrite: consumers dump this without --force, and an older .styleci.yml would let StyleCI revert mago's formatting.` The mago and workflow branches keep their `--force` behaviour unchanged.
3. Update the `--force` option text in `$signature` to `Overwrite existing config files (.styleci.yml is always overwritten)` so the help output stays accurate.
4. The repo dogfoods its own formatter. Run `php ringier-code-style fix app/Commands/ConfigDumpCommand.php` and confirm that `git diff app/Commands/ConfigDumpCommand.php` shows only the intended lines.

Commit .styleci.yml and app/Commands/ConfigDumpCommand.php: `fix(quick-261014-gpd): make StyleCI a no-op and always dump .styleci.yml`.
  </action>
  <verify>
    <automated>php -l app/Commands/ConfigDumpCommand.php && [ "$(grep -vE '^[[:space:]]*(#|$)' .styleci.yml | tr -d ' ' | paste -sd, -)" = "php:,preset:none" ] && ( R=$PWD; D=$(mktemp -d); cd "$D" && echo custom > .styleci.yml && echo custom > mago.toml && php "$R/ringier-code-style" config:dump --styleci --quiet && cmp .styleci.yml "$R/.styleci.yml" && echo custom > .styleci.yml && php "$R/ringier-code-style" config:dump --all --quiet && cmp .styleci.yml "$R/.styleci.yml" && [ "$(cat mago.toml)" = custom ] && php "$R/ringier-code-style" config:dump --all --force --quiet && cmp mago.toml "$R/mago.toml"; rc=$?; cd /; rm -rf "$D"; exit $rc )</automated>
  </verify>
  <done>.styleci.yml has only `php:` with `preset: none` plus the comment. Without `--force`, `config:dump --styleci` and `--all` replace a pre-existing custom .styleci.yml, and `--all` leaves a pre-existing mago.toml alone. With `--force`, mago.toml is replaced. Both files are committed.</done>
</task>

</tasks>

<threat_model>
## Trust Boundaries

| Boundary | Description |
|----------|-------------|
| this repo -> consumer repos | The bundled mago.toml and .styleci.yml are copied into consumer working trees by composer scripts and CI |
| executor -> private consumer clones | Private RingierIMU source is cloned locally for verification only |

## STRIDE Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation Plan |
|-----------|----------|-----------|----------|-------------|-----------------|
| T-261014-gpd-01 | Tampering | mago.toml excludes | medium | mitigate | `**/vendor/**`, `**/node_modules/**`, `storage`, `bootstrap/cache` and `**/*.blade.php` are excluded. The Task 1 and 2 gates fail on any tracked change under those paths, and on any change to the bootstrap/cache sentinel |
| T-261014-gpd-02 | Tampering | consumer clones | medium | mitigate | The verify commands run only clone, format, `php -l` and `git diff/status` inside a `mktemp -d` dir, with no git add, commit or push, and `rm -rf "$T"` on every exit path |
| T-261014-gpd-03 | Information disclosure | private source in temp clones | low | mitigate | Clones go in a per-run `mktemp -d` dir (mode 0700) and are deleted when each verify command ends |
| T-261014-gpd-04 | Tampering | ConfigDumpCommand always-overwrite of .styleci.yml | low | accept | It overwrites any consumer customisation. All three consumers' committed .styleci.yml match the bundled one, and the CI workflow stages only the fixed files, never the dumped configs |
| T-261014-gpd-SC | Tampering | composer install --no-scripts | low | accept | No new packages. The install restores exactly the committed composer.lock (checked with `git diff --quiet composer.lock`), so no package-legitimacy checkpoint is needed |
</threat_model>

<source_audit>
| Source | Item | Covered by |
|--------|------|------------|
| GOAL / REQ QUICK-261014-gpd-1 | StyleCI must not revert mago changes | Task 3 |
| GOAL / REQ QUICK-261014-gpd-2 | mago.toml works across the consumers' PHP versions and folder layouts | Tasks 1, 2 |
| CONTEXT D-01 | .styleci.yml becomes php preset none plus a one-line comment | Task 3 |
| CONTEXT D-02 | config:dump always overwrites .styleci.yml, other files keep `--force` | Task 3 |
| CONTEXT D-03 | php-version 8.3 with comment, paths line removed, excludes set, generic comment, [formatter] unchanged | Task 1 |
| CONTEXT D-04 | three-repo clone verification (a) to (d), per-repo counts, 8.4-syntax grep, clones deleted | Tasks 1, 2 |
| CONTEXT D-05 | no push/PR/branch, no PHAR rebuild, no consumer changes, no new abstractions | context block, all tasks |
</source_audit>

<verification>
- The Task 1 and Task 2 verify commands both exit 0, so all three consumers pass D-04 (a) to (d) with the committed mago.toml.
- The Task 3 verify command exits 0.
- `git status --short` shows only the pre-existing untracked `.claude/` and `.planning/codebase/`. builds/ringier-code-style is unchanged. `git log origin/main..main` shows the new commits locally and nothing is pushed.
</verification>

<success_criteria>
- StyleCI can no longer change any PHP file in a consumer that runs composer install with this version.
- The bundled mago.toml formats all three consumers without touching vendor, node_modules, storage, bootstrap/cache or Blade files, produces valid PHP 8.3 syntax, and is idempotent.
</success_criteria>

<output>
Create `.planning/quick/261014-gpd-make-styleci-a-no-op-and-mago-config-por/261014-gpd-SUMMARY.md` when done. Include the three per-repo `OK, N files changed` lines and any pre-existing parse failures found.
</output>
