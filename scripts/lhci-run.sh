#!/usr/bin/env bash
# Lighthouse CI — collect + assert + upload to filesystem
# Usage: bash scripts/lhci-run.sh [base_url]
set -euo pipefail

BASE_URL="${1:-http://localhost:8080}"
CHROME_PATH="${LHCI_CHROME_PATH:-}"

# Auto-detect Chrome for Testing from puppeteer cache if not set
if [[ -z "$CHROME_PATH" ]]; then
  CANDIDATE="$HOME/.cache/puppeteer/chrome/mac-140.0.7339.207/chrome-mac-x64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing"
  [[ -f "$CANDIDATE" ]] && CHROME_PATH="$CANDIDATE"
fi

export CHROME_PATH

PAGES=(
  "$BASE_URL/"
  "$BASE_URL/catalog"
  "$BASE_URL/account/login"
)

echo "=== Lighthouse CI ==="
echo "Base URL : $BASE_URL"
echo "Chrome   : ${CHROME_PATH:-system}"
echo "Pages    : ${#PAGES[@]}"
echo ""

npx lhci collect \
  --url="${PAGES[@]/#/--url=}" \
  --settings.chromeFlags="--no-sandbox --disable-dev-shm-usage" \
  --settings.formFactor=desktop \
  --settings.screenEmulation.disabled=true \
  --numberOfRuns=1

npx lhci assert
npx lhci upload --target=filesystem --outputDir=.lighthouseci

echo ""
echo "=== Done. Reports in .lighthouseci/ ==="
