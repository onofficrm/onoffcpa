#!/usr/bin/env bash
# OnOffCPA SPA 빌더 dist → onoff-builder-bridge imports 동기화
# 주의: linkconnect 저장소의 SPA 번들을 여기로 복사하면 안 됨(브랜드/카피 분리).
# 반드시 onoffcpa/builder/linkconnect_source 에서 npm run deploy:imports 로만 갱신.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/builder/linkconnect_source/dist"
PUBLIC="$ROOT/builder/linkconnect_source/public"
DEST="$ROOT/plugin/onoff-builder-bridge/imports/linkconnect"

if [[ ! -d "$SRC" ]]; then
  echo "dist 없음. 먼저 실행: cd builder/linkconnect_source && npm run build" >&2
  exit 1
fi

# 소스 dist 부터 브랜드 검증 (잘못된 빌드 동기화 방지)
bash "$ROOT/scripts/assert-onoffcpa-spa-branding.sh" "$SRC/index.html"

mkdir -p "$DEST/assets"
# Built assets (hashed JS/CSS + index.html) — replace
rsync -a --delete \
  --exclude '._*' \
  --exclude '.DS_Store' \
  --exclude '*.map' \
  "$SRC/assets/" "$DEST/assets/"
cp "$SRC/index.html" "$DEST/index.html"

# Static public assets (favicon, about images, webtoon) — merge, never wipe extras
if [[ -d "$PUBLIC" ]]; then
  rsync -a \
    --exclude '._*' \
    --exclude '.DS_Store' \
    "$PUBLIC/" "$DEST/"
fi

find "$DEST" -name '._*' -delete 2>/dev/null || true
find "$DEST" -name '.DS_Store' -delete 2>/dev/null || true

bash "$ROOT/scripts/assert-onoffcpa-spa-branding.sh" "$DEST/index.html"

echo "Synced: $SRC -> $DEST"
ls -la "$DEST/assets"
