#!/usr/bin/env bash
# LinkConnect 빌더 dist → onoff-builder-bridge imports 동기화
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/builder/linkconnect_source/dist"
DEST="$ROOT/plugin/onoff-builder-bridge/imports/linkconnect"

if [[ ! -d "$SRC" ]]; then
  echo "dist 없음. 먼저 실행: cd builder/linkconnect_source && npm run build" >&2
  exit 1
fi

mkdir -p "$DEST/assets"
rsync -a --delete \
  --exclude '._*' \
  --exclude '.DS_Store' \
  "$SRC/" "$DEST/"

echo "Synced: $SRC -> $DEST"
ls -la "$DEST/assets"
