#!/usr/bin/env bash
#
# Local CI for bp-group-name-suggester: PHP lint, WPCS, PHPStan, JSON validity
# of the QA files and version consistency across header, constant and readme.
#
# Usage: bash bin/local-ci.sh
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
fail() { printf '\033[31mFAIL: %s\033[0m\n' "$*"; exit 1; }
step() { printf '\033[34m[%s] %s\033[0m\n' "$1" "$2"; }

step 1 "PHP syntax lint"
find . -name '*.php' -not -path './node_modules/*' -not -path './vendor/*' -not -path './dist/*' -print0 | xargs -0 -n 1 php -l > /dev/null || fail "PHP syntax errors"

step 2 "WPCS (phpcs.xml)"
if command -v phpcs > /dev/null; then
	phpcs -q --report=summary || fail "WPCS errors"
else
	echo "    phpcs not installed: skipped"
fi

step 3 "PHPStan (phpstan.neon)"
if command -v phpstan > /dev/null; then
	phpstan analyse --no-progress --memory-limit=1G > /dev/null || fail "PHPStan errors (run: phpstan analyse)"
else
	echo "    phpstan not installed: skipped"
fi

step 4 "QA JSON files are valid"
for f in audit/manifest.json docs/qa/qa-config.json; do
	[ -f "$f" ] || fail "$f missing"
	php -r 'json_decode(file_get_contents($argv[1]), false, 512, JSON_THROW_ON_ERROR);' "$f" || fail "$f is not valid JSON"
done

step 5 "Version consistency"
SLUG="bp-group-name-suggester"
HDR="$(grep -m1 -E '^[[:space:]]*\*[[:space:]]*Version:' "$SLUG.php" | sed -E 's/.*Version:[[:space:]]*//; s/[[:space:]]*$//')"
CONST="$(grep -m1 "const VERSION" "$SLUG.php" | sed -E "s/.*'([0-9][^']*)'.*/\1/")"
TAG="$(grep -m1 '^Stable tag:' readme.txt | sed -E 's/Stable tag:[[:space:]]*//; s/[[:space:]]*$//')"
[ "$HDR" = "$CONST" ] && [ "$HDR" = "$TAG" ] || fail "version mismatch: header=$HDR const=$CONST readme=$TAG"
grep -qE "^= $HDR" readme.txt || fail "readme.txt changelog has no entry for $HDR"
echo "    $HDR"

echo "local CI passed"
