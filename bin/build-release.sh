#!/usr/bin/env bash
#
# Build dist/bp-group-name-suggester-<version>.zip from the tree, excluding .distignore entries.
#
# Usage: bash bin/build-release.sh
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
fail() { printf '\033[31mFAIL: %s\033[0m\n' "$*"; exit 1; }

SLUG="bp-group-name-suggester"
bash bin/local-ci.sh
VER="$(grep -m1 -E '^[[:space:]]*\*[[:space:]]*Version:' "$SLUG.php" | sed -E 's/.*Version:[[:space:]]*//; s/[[:space:]]*$//')"

STAGE="dist/_stage/$SLUG"
rm -rf dist/_stage "dist/$SLUG-$VER.zip"
mkdir -p "$STAGE"
rsync -a --exclude-from=.distignore ./ "$STAGE/"
(cd dist/_stage && zip -qr -9 "../$SLUG-$VER.zip" "$SLUG")
rm -rf dist/_stage

LEAKS="$(unzip -Z1 "dist/$SLUG-$VER.zip" | grep -E '(^|/)(audit|bin|docs|node_modules)/|\.md$|\.map$|phpcs\.xml|phpstan|package(-lock)?\.json' || true)"
[ -z "$LEAKS" ] || fail "dev files in zip: $LEAKS"
echo "dist/$SLUG-$VER.zip"
