#!/usr/bin/env bash
# Re-apply ERP layout / design-system rollout from saved snapshot.
# Usage: bash web/scripts/apply-layout-system.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DESIGN="$ROOT/.rollback-layout-backup/design-rollout/files"

if [[ ! -d "$DESIGN" ]]; then
  echo "Design rollout snapshot not found at $DESIGN"
  exit 1
fi

echo "Applying design-system rollout..."
while IFS= read -r -d '' src; do
  rel="${src#$DESIGN/}"
  [[ -z "$rel" ]] && continue
  dest="$ROOT/$rel"
  mkdir -p "$(dirname "$dest")"
  cp "$src" "$dest"
  echo "  applied: $rel"
done < <(find "$DESIGN" -type f -print0)

# Ensure layout helper is loaded app-wide
if ! grep -q "ew_page_layout.php" "$ROOT/include/function.php" 2>/dev/null; then
  sed -i "/encryption.php/a require_once(__DIR__ . '/ew_page_layout.php');" "$ROOT/include/function.php"
  echo "  patched: include/function.php (ew_page_layout.php)"
fi

echo ""
echo "Design rollout applied. Hard-refresh the browser (Ctrl+Shift+R)."
