#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
REPO_ROOT="$(CDPATH= cd -- "${SCRIPT_DIR}/.." && pwd)"

echo "[pre-commit] Running Angular lint..."
(cd "${REPO_ROOT}/itcph2" && CI=1 npx ng lint)

echo "[pre-commit] Running SCSS/CSS lint..."
(cd "${REPO_ROOT}/itcph2" && npm run scss_lint_using_sass_lint && npm run css_lint_using_stylelint)

echo "[pre-commit] Running PHP lint on staged files..."
php "${REPO_ROOT}/scripts/php_lint.php" --staged

if [ "${PRECOMMIT_PHP_CS_FIXER:-0}" = "1" ]; then
  echo "[pre-commit] Running optional PHP CS Fixer dry-run..."
  if ! command -v php-cs-fixer >/dev/null 2>&1; then
    echo "[pre-commit] php-cs-fixer is not installed, but PRECOMMIT_PHP_CS_FIXER=1."
    exit 1
  fi
  (cd "${REPO_ROOT}" && php-cs-fixer fix --dry-run --diff --config=itcph2/.php-cs-fixer.php)
fi

echo "[pre-commit] All checks passed."
