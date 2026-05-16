# Automation Quick SOP

Use this as the team’s minimal day-to-day process.

## One-time setup

1. Install Node.js + npm.
2. Install PHP CLI (make sure `php` works in terminal).
3. Install frontend dependencies:
   - `cd itcph2`
   - `npm ci`
   - `cd ..`
4. Enable repo hook:
   - `git config core.hooksPath .githooks`

## What runs automatically

On every `git commit`:

- Angular lint: `npx ng lint` (inside `itcph2`)
- PHP staged lint: `php scripts/php_lint.php --staged`
- Optional PHP CS Fixer dry-run when `PRECOMMIT_PHP_CS_FIXER=1`

On every push/PR (GitHub Actions in `.github/workflows/ci.yml`):

- Angular lint job
- PHP lint job
- PHPStan job (static analysis on `scripts/` and `itcph2/dist/browser/services/class/`)
- PHP CS Fixer job (style check using `itcph2/.php-cs-fixer.php`)

## Daily workflow

1. Pull latest code.
2. Develop.
3. Commit (local checks auto-run).
4. Push and create PR (CI auto-runs).
5. Merge only after CI is green.

## Manual commands (when needed)

- Run pre-commit checks manually (PowerShell):
  - `powershell -ExecutionPolicy Bypass -File scripts/pre-commit-checks.ps1`
- Run pre-commit with optional local PHP CS Fixer (PowerShell):
  - `$env:PRECOMMIT_PHP_CS_FIXER="1"; powershell -ExecutionPolicy Bypass -File scripts/pre-commit-checks.ps1`
- Lint all tracked PHP files:
  - `php scripts/php_lint.php`
- Lint only staged PHP files:
  - `php scripts/php_lint.php --staged`
- Run PHPStan locally (if installed):
  - `phpstan analyse -c phpstan.neon --no-progress`
- Run PHP CS Fixer check locally (if installed):
  - `php-cs-fixer fix --dry-run --diff --config=itcph2/.php-cs-fixer.php`

## Quick fixes

- Hook not running:
  - `git config core.hooksPath .githooks`
- Angular command issues:
  - `cd itcph2 && npm ci`
- PHP command not found:
  - Install PHP CLI and add it to PATH
