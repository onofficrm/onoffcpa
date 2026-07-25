#!/usr/bin/env bash
# LinkConnect 빌더 dist → onoff-builder-bridge imports 동기화
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/builder/linkconnect_source/dist"
PUBLIC="$ROOT/builder/linkconnect_source/public"
DEST="$ROOT/plugin/onoff-builder-bridge/imports/linkconnect"

if [[ ! -d "$SRC" ]]; then
  echo "dist 없음. 먼저 실행: cd builder/linkconnect_source && npm run build" >&2
  exit 1
fi

mkdir -p "$DEST/assets"
# Built assets (hashed JS/CSS + index.html) — replace
rsync -a --delete \
  --exclude '._*' \
  --exclude '.DS_Store' \
  "$SRC/assets/" "$DEST/assets/"
cp "$SRC/index.html" "$DEST/index.html"

# Static public assets (favicon, about images, webtoon) — merge, never wipe extras
if [[ -d "$PUBLIC" ]]; then
  rsync -a \
    --exclude '._*' \
    --exclude '.DS_Store' \
    "$PUBLIC/" "$DEST/"
fi

echo "Synced: $SRC -> $DEST"
ls -la "$DEST/assets"
