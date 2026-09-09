#!/usr/bin/env bash
# Revert ERP layout / design-system rollout to pre-design (legacy) UI.
# Usage: bash web/scripts/rollback-layout-system.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
REPO="$(cd "$ROOT/.." && pwd)"
PREDESIGN="$ROOT/.rollback-layout-backup/pre-design/files"
GIT_TRACKED=(
  branch.php
  branch_list.php
  client_list.php
  pickup_list.php
  request_for_new_pickup.php
  vendor_list.php
  invoice_list.php
  transaction_list.php
  include/css_js.php
  include/function.php
)

echo "Rolling back to pre-design layout..."

if [[ -d "$REPO/.git" ]]; then
  echo "  restoring git-tracked pages from HEAD..."
  (
    cd "$REPO"
    git checkout HEAD -- $(printf 'web/%s ' "${GIT_TRACKED[@]}")
  )
  for rel in "${GIT_TRACKED[@]}"; do
    echo "  restored (git): $rel"
  done
fi

if [[ -d "$PREDESIGN" ]]; then
  echo "  restoring files from pre-design backup..."
  while IFS= read -r -d '' src; do
    rel="${src#$PREDESIGN/}"
    [[ -z "$rel" ]] && continue
    dest="$ROOT/$rel"
    mkdir -p "$(dirname "$dest")"
    cp "$src" "$dest"
    echo "  restored (backup): $rel"
  done < <(find "$PREDESIGN" -type f -print0 2>/dev/null || true)
fi

rm -f "$ROOT/include/ew_page_layout.php"

echo ""
echo "Rollback complete. Hard-refresh the browser (Ctrl+Shift+R)."
echo "To re-apply the design rollout later: bash web/scripts/apply-layout-system.sh"
echo "Design snapshot preserved at: $ROOT/.rollback-layout-backup/design-rollout/"
