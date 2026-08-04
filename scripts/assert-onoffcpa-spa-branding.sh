#!/usr/bin/env bash
# onoffcpa imports/linkconnect SPA 브랜드 가드
# linkconnect 저장소 SPA를 잘못 복사하면 홈이 '링크커넥트'로 덮인다.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
REQUIRE_LOCK=0
if [[ "${1:-}" == "--require-lock" ]]; then
  REQUIRE_LOCK=1
  shift
fi

INDEX="${1:-$ROOT/plugin/onoff-builder-bridge/imports/linkconnect/index.html}"
DIR="$(cd "$(dirname "$INDEX")" && pwd)"
LOCK="$DIR/spa-brand.onoffcpa"

fail() {
  echo "assert-onoffcpa-spa-branding: FAIL — $1" >&2
  echo "  → onoffcpa/builder/linkconnect_source 에서 npm run deploy:imports 로만 갱신하세요." >&2
  echo "  → linkconnect 저장소 imports/linkconnect 를 rsync/복사하지 마세요." >&2
  exit 1
}

if [[ ! -f "$INDEX" ]]; then
  fail "index.html 없음: $INDEX"
fi

# 배포 산출물(imports)은 lock 필수. dist 사전검증은 --require-lock 없이도 HTML만 검사.
if [[ "$REQUIRE_LOCK" -eq 1 ]] || [[ "$DIR" == */plugin/onoff-builder-bridge/imports/linkconnect ]]; then
  if [[ ! -f "$LOCK" ]]; then
    fail "브랜드 lock 없음: $LOCK (동기화 스크립트가 생성해야 함)"
  fi
  if ! grep -qE '^brand=onoffcpa$' "$LOCK"; then
    fail "브랜드 lock 내용 불일치: $LOCK"
  fi
fi

TITLE="$(grep -oE '<title>[^<]*</title>' "$INDEX" | head -1 || true)"
if [[ -z "$TITLE" ]]; then
  fail "title 태그 없음 ($INDEX)"
fi
if echo "$TITLE" | grep -qE '링크커넥트|LinkConnect'; then
  fail "title 에 링크커넥트/LinkConnect 포함: $TITLE"
fi
if ! echo "$TITLE" | grep -qE '온오프CPA|OnOff CPA'; then
  fail "title 에 온오프CPA/OnOff CPA 없음: $TITLE"
fi

# HTML 본문에 온오프CPA 브랜드가 있고, 잘못된 사이트 타이틀 패턴이 없어야 함
if ! grep -qE '온오프CPA|OnOff CPA' "$INDEX"; then
  fail "온오프CPA/OnOff CPA 브랜드 문자열이 없음 ($INDEX)"
fi
if grep -qE '<title>[^<]*링크커넥트|<meta[^>]+content="[^"]*링크커넥트 \|' "$INDEX"; then
  fail "링크커넥트 메타/타이틀 패턴 감지 ($INDEX)"
fi

# 메인 번들에도 온오프CPA가 있고, 링크커넥트 전용 타이틀 문자열이 없어야 함
JS_REL="$(grep -oE 'assets/index-[A-Za-z0-9_-]+\.js' "$INDEX" | head -1 || true)"
if [[ -n "$JS_REL" ]]; then
  JS_FILE="$DIR/$JS_REL"
  if [[ ! -f "$JS_FILE" ]]; then
    # dist 에서는 assets/ 가 DIR 아래
    if [[ -f "$DIR/assets/${JS_REL#assets/}" ]]; then
      JS_FILE="$DIR/assets/${JS_REL#assets/}"
    fi
  fi
  if [[ -f "$JS_FILE" ]]; then
    if ! grep -q '온오프CPA' "$JS_FILE"; then
      fail "메인 JS 에 온오프CPA 없음: $JS_FILE"
    fi
    if grep -qE '링크커넥트 \| CPA CPS|링크커넥트 \| CPA/CPS' "$JS_FILE"; then
      fail "메인 JS 에 링크커넥트 사이트 타이틀 패턴 감지: $JS_FILE"
    fi
  fi
fi

echo "assert-onoffcpa-spa-branding: OK ($INDEX)"
exit 0
