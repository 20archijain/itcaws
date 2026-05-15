#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
REPO_ROOT="$(CDPATH= cd -- "${SCRIPT_DIR}/.." && pwd)"

echo "[pre-commit] Running Angular lint..."
(cd "${REPO_ROOT}/itcph2" && CI=1 npx ng lint)

echo "[pre-commit] Running PHP lint on staged files..."
php "${REPO_ROOT}/scripts/php_lint.php" --staged

echo "[pre-commit] All checks passed."
