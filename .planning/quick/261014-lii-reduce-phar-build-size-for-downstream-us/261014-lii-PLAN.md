---
phase: quick-261014-lii
plan: 01
type: execute
wave: 1
depends_on: []
files_modified:
  - box.json
autonomous: true
requirements:
  - QUICK-261014-lii-1

estimate:
  tokens: 25000
  raw_tokens: 25000
  tasks: 1
  confidence: low

must_haves:
  truths:
    - "A PHAR built from box.json has no entries under vendor/laravel-zero/framework/bin/, vendor/jolicode/jolinotif/bin/, vendor/jolicode/jolinotif/doc/, vendor/jolicode/jolinotif/tools/ or vendor/jolicode/php-os-helper/tools/ (QUICK-261014-lii-1)"
    - "The PHAR still carries vendor/symfony/console/Resources/bin/hiddeninput.exe, jolinotif src/ classes and the package LICENSE files"
    - "The built PHAR is at least 3,000,000 bytes smaller than the committed builds/ringier-code-style (QUICK-261014-lii-1)"
    - "The built PHAR passes the release workflow's smoke tests (--version, config:dump, fix:stdin) and `fix composer.json` in an empty scratch dir"
    - "Only box.json is committed. builds/ringier-code-style is never rebuilt or committed"
  artifacts:
    - path: box.json
      provides: "finder[0].exclude lists carthage-software/mago/composer/bin plus the five new vendor dirs"
  key_links:
    - from: box.json
      to: "builds PHAR"
      via: "finder `in: vendor` + `exclude`. Box calls Symfony Finder exclude() once per entry, which drops directories only, relative to vendor/"
---

<objective>
Shrink the PHAR that every consumer installs. Add five directory entries to the existing box.json finder `exclude` list. Together they hold about 3.4 MB of compressed dead weight:
- laravel-zero's bundled box builder
- jolinotif's Windows notifier executables
- jolinotif's demo docs
- the dev-only `tools/` dirs of both jolicode packages

Purpose: `composer require` ships only the PHAR (.gitattributes export-ignores everything else), so the PHAR is the only thing to cut. Expected result: about 7.7 MB down to about 4.2 MB.
Output: box.json edited and committed alone. builds/ringier-code-style stays untouched, because the Release workflow owns it.
</objective>

<execution_context>
@~/.claude/gsd-core/workflows/execute-plan.md
@~/.claude/gsd-core/templates/summary.md
</execution_context>

<context>
@box.json

Execution rules:
- Run on the current branch (1.x) in the main working tree. One local commit. No push, no PR, no branch.
- Never commit builds/ringier-code-style, because the Release workflow builds and commits it. Precedent: commit 4a02cd3 changed only box.json.
- The verification build goes to builds/rcs-verify. The verify command deletes that file on exit, so builds/ringier-code-style is never rewritten and needs no `git checkout`.
- Change only box.json. Leave `.planning/codebase/` (untracked) alone.

Facts (measured at planning time, do not re-derive):
- `vendor/laravel-zero/framework/bin/` contains only `box` (2.9 MB raw) and `box.bat`. `app:build` runs box from the source tree, never from inside the PHAR. laravel-zero/framework declares no composer `bin`, so vendor/bin has no proxy pointing into that dir.
- `vendor/jolicode/jolinotif/bin/` holds only `notifu/` and `snoreToast/`: the Windows .exe files, their READMEs and snoreToast's own LICENSE. That LICENSE goes along with the exe it covers. The jolinotif package LICENSE at the package root stays.
- Nothing in app/ or config/ calls `notify`. jolinotif's `src/` stays bundled, so its classes still autoload.
- Box Configuration::processFinder passes each `exclude` entry to Symfony Finder `exclude()`. That method only drops directories, matched relative to `in: vendor`. A file path such as `laravel-zero/framework/bin/box` would be ignored with no error. That is why every new entry is a directory, in the same form as the existing `carthage-software/mago/composer/bin`.
- The committed builds/ringier-code-style is 7,671,893 bytes. CI built it from the current box.json at tag 1.0.0, so it is the "before" baseline.
- The release workflow builds after a full `composer install`, including dev packages. The local vendor/ matches that.
</context>

<tasks>

<task type="tracer">
  <name>Task 1: Exclude the dead vendor dirs in box.json and prove it with a built PHAR</name>
  <files>box.json</files>
  <action>
1. box.json: in `finder[0].exclude`, keep `carthage-software/mago/composer/bin` and append these five strings, in this order:
   - `laravel-zero/framework/bin`
   - `jolicode/jolinotif/bin`
   - `jolicode/jolinotif/doc`
   - `jolicode/jolinotif/tools`
   - `jolicode/php-os-helper/tools`

   Keep the existing 4-space JSON indentation. Change nothing else: no new options, no `notPath` or regex filters, no changes to `exclude-dev-files`.

2. Do NOT exclude any of these:
   - `symfony/console/Resources/bin`: hiddeninput.exe is the Windows helper that reads hidden input at runtime.
   - any package LICENSE file. They are the MIT redistribution notice.
   - jolinotif `src/`.
   - Carbon `src/Carbon/Lang`, package *.md docs, or the laravel-zero/foundation `dist/scripts.js`. These are out of scope for this task.

3. Run the verify command from the repo root with a Bash timeout of 600000 ms. It does these checks:
   - builds builds/rcs-verify at version 1.0.1-dev;
   - confirms app:build restored config/ and left builds/ringier-code-style unchanged;
   - asserts the exact exclude set in box.json;
   - prints and compares the before and after byte sizes;
   - asserts the five dirs are absent from the PHAR and the kept files are present;
   - runs the release workflow's three smoke tests: `--version`, `config:dump --all --workflow` diffed against stubs/, and `fix:stdin` formatting a snippet;
   - runs `fix composer.json` on a minimal composer.json in an empty temp dir.

   The fix:stdin and fix checks need mago's platform binary, which may download on first run. If they fail on network, report that. Do not skip them.

4. If the verify command fails, find the root cause and fix it inside box.json, then rerun. Some failures cannot be fixed in box.json:
   - a removed dir turns out to be needed at runtime;
   - the size drop is below 3,000,000 bytes.

   In either case, stop and report the failing check and its output. Do not widen or narrow the exclude list beyond step 1.

5. Copy the `PHAR bytes: before=... after=...` line from the verify output into the SUMMARY.

6. Run `git status --porcelain`. Expect only ` M box.json` and the untracked `.planning/` entries. Stage only box.json and commit. Use this subject:

   fix: keep build-time and notifier binaries out of the PHAR

   Use a two-line body:
   - Line 1: box only runs at build time (app:build) and the app never sends desktop notifications, so their binaries, docs and dev tooling were dead weight in the PHAR every consumer installs.
   - Line 2: PHAR: BEFORE -> AFTER bytes. Replace BEFORE and AFTER with the measured numbers.

   End the message with the line `Co-Authored-By: Claude Opus 5.5 (1M context) <noreply@anthropic.com>`.

   If `git commit` fails on signing (1Password agent), stop and report. Do not retry with signing disabled.
  </action>
  <verify>
    <automated>( set -euo pipefail; R=$PWD; T=$(mktemp -d); trap 'rm -rf "$T" "$R/builds/rcs-verify"' EXIT; B="$R/builds/rcs-verify"; ./ringier-code-style app:build rcs-verify --build-version=1.0.1-dev > /dev/null; php -r '$e = json_decode(file_get_contents("box.json"), true)["finder"][0]["exclude"]; $w = ["carthage-software/mago/composer/bin", "laravel-zero/framework/bin", "jolicode/jolinotif/bin", "jolicode/jolinotif/doc", "jolicode/jolinotif/tools", "jolicode/php-os-helper/tools"]; exit($e === $w ? 0 : 1);'; before=$(php -r 'echo filesize($argv[1]);' builds/ringier-code-style); after=$(php -r 'echo filesize($argv[1]);' "$B"); echo "PHAR bytes: before=$before after=$after"; [ "$after" -lt $((before - 3000000)) ]; cp "$B" "$T/v.phar"; php -r '$p = new Phar($argv[1]); foreach (["vendor/symfony/console/Resources/bin/hiddeninput.exe", "vendor/jolicode/jolinotif/LICENSE", "vendor/jolicode/jolinotif/src/DefaultNotifier.php", "vendor/jolicode/php-os-helper/LICENSE", "vendor/laravel-zero/framework/LICENSE.md"] as $k) { if (! isset($p[$k])) { fwrite(STDERR, "missing $k\n"); exit(1); } } $gone = ["vendor/laravel-zero/framework/bin/", "vendor/jolicode/jolinotif/bin/", "vendor/jolicode/jolinotif/doc/", "vendor/jolicode/jolinotif/tools/", "vendor/jolicode/php-os-helper/tools/"]; $n = 0; foreach (new RecursiveIteratorIterator($p) as $f) { $n++; foreach ($gone as $g) { if (str_contains($f->getPathname(), "v.phar/" . $g)) { fwrite(STDERR, "still bundled " . $f->getPathname() . "\n"); exit(1); } } } exit($n > 1000 ? 0 : 1);' "$T/v.phar"; v=$("$B" --version --no-ansi | tr -d '\r'); case "$v" in *1.0.1-dev) ;; *) echo "bad version: $v" >&2; exit 1 ;; esac; mkdir "$T/dump"; ( cd "$T/dump"; "$B" config:dump --all --workflow --no-ansi > /dev/null ); test -s "$T/dump/mago.toml"; test -s "$T/dump/.github/workflows/ringier-code-style.yml"; diff -q "$T/dump/.github/workflows/ringier-code-style.yml" stubs/.github/workflows/ringier-code-style.yml; mkdir "$T/stdin"; out=$(cd "$T/stdin"; printf '\074?php\n$a=[1,2];\n' | "$B" fix:stdin); [ "$out" = "$(printf '\074?php\n\n$a = [1, 2];')" ]; mkdir "$T/n"; cd "$T/n"; printf '{\n  "require": {"ext-json": "*", "php": ">=8.3"},\n  "name": "acme/scratch",\n  "description": "Scratch"\n}\n' > composer.json; "$B" fix composer.json; test ! -e composer.lock; php -r 'exit(array_key_first(json_decode(file_get_contents("composer.json"), true)["require"]) === "php" ? 0 : 1);'; grep -q '"name": "acme/scratch"' composer.json; s=$(git -C "$R" status --porcelain -- config builds/ringier-code-style); [ -z "$s" ] )</automated>
  </verify>
  <done>box.json `finder[0].exclude` is exactly the six dirs, in order. A PHAR built from it:
- has none of the five excluded dirs;
- still has hiddeninput.exe, jolinotif src/ and the LICENSE files;
- is at least 3,000,000 bytes smaller than the committed builds/ringier-code-style;
- reports version 1.0.1-dev;
- dumps a mago.toml and a workflow that matches stubs/;
- formats the fix:stdin snippet;
- normalizes a scratch composer.json without creating a lock.

The commit touches only box.json. builds/ringier-code-style is unchanged and builds/rcs-verify is gone.</done>
</task>

</tasks>

<threat_model>
## Trust Boundaries

| Boundary | Description |
|----------|-------------|
| this repo's vendor/ → PHAR | box.json decides which vendor files ship in the binary every consumer runs |
| local build → release artifact | Only the Release workflow may produce and commit builds/ringier-code-style |

## STRIDE Threat Register

| Threat ID | Category | Component | Severity | Disposition | Mitigation Plan |
|-----------|----------|-----------|----------|-------------|-----------------|
| T-261014-lii-01 | Denial of Service | box.json finder exclude | high | mitigate | Directory-level excludes are limited to the five dirs measured as unused at runtime. Verify asserts that hiddeninput.exe, jolinotif src/ and the LICENSE files are still bundled, and runs the release workflow's smoke tests plus `fix composer.json` against the built PHAR, so a runtime dependency on a removed dir fails before commit |
| T-261014-lii-02 | Tampering | builds/ringier-code-style | medium | mitigate | The verify build writes builds/rcs-verify, which is deleted on exit. Verify asserts a clean working-tree status for builds/ringier-code-style and config/, and the commit stages only box.json, so no locally built binary bypasses the Release workflow's checks |
| T-261014-lii-SC | Tampering | npm/pip/cargo installs | high | accept | No package installs in this plan. Only box.json changes, and composer.json and composer.lock are untouched |
</threat_model>

<verification>
The task verify command passes from the repo root. `git log -1 --stat` shows one commit that touches only box.json. `git status --porcelain` shows no tracked changes and no builds/rcs-verify.
</verification>

<success_criteria>
- The five dead vendor dirs are excluded through the existing box.json `exclude` list, and nothing else changed.
- The PHAR consumers install shrinks by at least 3,000,000 bytes. The measured before and after sizes are recorded.
- The built PHAR passes the same smoke tests the Release workflow runs.
</success_criteria>

<output>
Create `.planning/quick/261014-lii-reduce-phar-build-size-for-downstream-us/261014-lii-SUMMARY.md` when done. Record:
- the `PHAR bytes: before=... after=...` line;
- the percentage reduction;
- the commit sha;
- whether fix:stdin and fix had to download the mago binary.
</output>
