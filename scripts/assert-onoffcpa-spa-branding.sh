#!/usr/bin/env bash
# onoffcpa imports/linkconnect SPA 브랜드 가드
# linkconnect 저장소 SPA를 잘못 복사하면 홈이 '링크커넥트'로 덮인다.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
INDEX="${1:-$ROOT/plugin/onoff-builder-bridge/imports/linkconnect/index.html}"

if [[ ! -f "$INDEX" ]]; then
  echo "assert-onoffcpa-spa-branding: index.html 없음: $INDEX" >&2
  exit 1
fi

if grep -qE '온오프CPA|OnOff CPA' "$INDEX"; then
  if grep -qE '<title>[^<]*링크커넥트' "$INDEX"; then
    echo "assert-onoffcpa-spa-branding: FAIL — title 에 링크커넥트가 포함됨 ($INDEX)" >&2
    exit 1
  fi
  echo "assert-onoffcpa-spa-branding: OK ($INDEX)"
  exit 0
fi

echo "assert-onoffcpa-spa-branding: FAIL — 온오프CPA/OnOff CPA 브랜드 문자열이 없음 ($INDEX)" >&2
echo "  → onoffcpa/builder/linkconnect_source 에서 npm run deploy:imports 로만 갱신하세요." >&2
echo "  → linkconnect 저장소 imports/linkconnect 를 rsync 하지 마세요." >&2
exit 1
