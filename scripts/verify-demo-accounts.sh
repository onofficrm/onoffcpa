#!/usr/bin/env bash
# LinkConnect 데모 계정 동작 검증 (프로덕션)
set -euo pipefail

BASE_URL="${1:-https://linkconnect.co.kr}"
PASSWORD="${2:-demo1234!}"
PARTNER_MB="${3:-lc_partner}"
ADVERTISER_MB="${4:-lc_advertiser}"

PASS=0
FAIL=0
WARN=0

ok() { echo "  ✅ $1"; PASS=$((PASS + 1)); }
ng() { echo "  ❌ $1"; FAIL=$((FAIL + 1)); }
warn() { echo "  ⚠️  $1"; WARN=$((WARN + 1)); }

g5_login() {
  local mb_id="$1"
  local cookie_file="$2"
  rm -f "$cookie_file"
  curl -sS -c "$cookie_file" -b "$cookie_file" "${BASE_URL}/bbs/login.php" -o /dev/null
  local code
  code=$(curl -sS -c "$cookie_file" -b "$cookie_file" -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/bbs/login_check.php" \
    -d "mb_id=${mb_id}" \
    -d "mb_password=${PASSWORD}" \
    -d "url=%2F")
  if [[ "$code" == "302" || "$code" == "200" ]]; then
    return 0
  fi
  return 1
}

api_get() {
  local cookie_file="$1"
  local url="$2"
  curl -sS -b "$cookie_file" "$url"
}

json_ok() {
  python3 -c "import sys,json; d=json.load(sys.stdin); sys.exit(0 if d.get('ok') else 1)" 2>/dev/null
}

json_field() {
  local expr="$1"
  python3 -c "import sys,json; d=json.load(sys.stdin); print($expr)" 2>/dev/null
}

check_account() {
  local role="$1"
  local mb_id="$2"
  local cookie_file="/tmp/lc_check_${mb_id}.txt"

  echo ""
  echo "━━━ ${role} (${mb_id}) ━━━"

  if ! g5_login "$mb_id" "$cookie_file"; then
    ng "그누보드 로그인 실패"
    return
  fi
  ok "그누보드 로그인 성공"

  if [[ "$role" == "파트너" ]]; then
    local me_url="${BASE_URL}/plugin/linkconnect/partner/api/me.php"
    local me
    me=$(api_get "$cookie_file" "$me_url")
    if echo "$me" | grep -q 'Fatal error'; then
      ng "partner/me.php PHP 오류: $(echo "$me" | tr '\n' ' ' | head -c 120)"
      return
    fi
    if echo "$me" | json_ok; then
      ok "partner/me.php 응답 ok"
      local mid pcode active
      mid=$(echo "$me" | json_field "d['data']['auth']['memberId']")
      pcode=$(echo "$me" | json_field "d['data']['partner']['code']")
      active=$(echo "$me" | json_field "d['data']['auth']['isActivePartner']")
      if [[ "$mid" == "$mb_id" ]]; then ok "auth.memberId = ${mid}"; else ng "auth.memberId 불일치: ${mid}"; fi
      if [[ -n "$pcode" && "$pcode" != "None" ]]; then ok "partner.code = ${pcode}"; else ng "partner.code 없음"; fi
      if [[ "$active" == "True" ]]; then ok "isActivePartner = true"; else ng "isActivePartner = ${active}"; fi
    else
      ng "partner/me.php 실패: $(echo "$me" | head -c 200)"
    fi

    local camps
    camps=$(api_get "$cookie_file" "${BASE_URL}/plugin/linkconnect/partner/api/campaigns.php")
    if echo "$camps" | json_ok; then
      local cnt
      cnt=$(echo "$camps" | json_field "len(d['data']['items'])")
      if [[ "$cnt" -gt 0 ]]; then ok "partner/campaigns.php 캠페인 ${cnt}건"; else warn "partner/campaigns.php 캠페인 0건"; fi
    else
      ng "partner/campaigns.php 실패"
    fi

    local spa
    spa=$(curl -sS -b "$cookie_file" "${BASE_URL}/partner/")
    if echo "$spa" | grep -q 'window.__LC_AUTH__'; then
      local spa_active
      spa_active=$(echo "$spa" | grep -o 'window.__LC_AUTH__=[^<]*' | head -1 | python3 -c "
import sys,re,json
line=sys.stdin.read().strip()
m=re.search(r'window.__LC_AUTH__=(.+)', line)
if not m: sys.exit(1)
d=json.loads(m.group(1))
print('yes' if d.get('loggedIn') and d.get('isActivePartner') else 'no')
" 2>/dev/null || echo "no")
      if [[ "$spa_active" == "yes" ]]; then ok "SPA /partner/ 접근 (isActivePartner)"; else ng "SPA /partner/ auth 부족"; fi
    elif echo "$spa" | grep -q 'id="root"'; then
      warn "SPA HTML 로드됐으나 __LC_AUTH__ 미주입"
    else
      ng "SPA /partner/ 로드 실패"
    fi
  fi

  if [[ "$role" == "광고주" ]]; then
    local me_url="${BASE_URL}/plugin/linkconnect/merchant/api/me.php"
    local me
    me=$(api_get "$cookie_file" "$me_url")
    if echo "$me" | grep -q 'Fatal error'; then
      ng "merchant/me.php PHP 오류"
      return
    fi
    if echo "$me" | json_ok; then
      ok "merchant/me.php 응답 ok"
      local mid mcode active
      mid=$(echo "$me" | json_field "d['data']['auth']['memberId']")
      mcode=$(echo "$me" | json_field "d['data']['merchant']['code']")
      active=$(echo "$me" | json_field "d['data']['auth']['isActiveMerchant']")
      if [[ "$mid" == "$mb_id" ]]; then ok "auth.memberId = ${mid}"; else ng "auth.memberId 불일치: ${mid}"; fi
      if [[ -n "$mcode" && "$mcode" != "None" ]]; then ok "merchant.code = ${mcode}"; else ng "merchant.code 없음"; fi
      if [[ "$active" == "True" ]]; then ok "isActiveMerchant = true"; else ng "isActiveMerchant = ${active}"; fi
    else
      ng "merchant/me.php 실패: $(echo "$me" | head -c 200)"
    fi

    local dash
    dash=$(api_get "$cookie_file" "${BASE_URL}/plugin/linkconnect/merchant/api/dashboard.php")
    if echo "$dash" | json_ok; then
      local bal recent
      bal=$(echo "$dash" | json_field "d['data']['balance']")
      recent=$(echo "$dash" | json_field "len(d['data']['recent'])")
      ok "merchant/dashboard.php 잔액 ${bal}원, 최근전환 ${recent}건"
    else
      ng "merchant/dashboard.php 실패"
    fi

    local conv
    conv=$(api_get "$cookie_file" "${BASE_URL}/plugin/linkconnect/merchant/api/conversions.php")
    if echo "$conv" | json_ok; then
      local total pending
      total=$(echo "$conv" | json_field "d['data']['total']")
      pending=$(echo "$conv" | json_field "d['data']['summary']['pending']")
      ok "merchant/conversions.php 전환 ${total}건 (대기 ${pending})"
    else
      ng "merchant/conversions.php 실패"
    fi

    local wallet
    wallet=$(api_get "$cookie_file" "${BASE_URL}/plugin/linkconnect/merchant/api/wallet.php")
    if echo "$wallet" | json_ok; then
      local items
      items=$(echo "$wallet" | json_field "len(d['data']['items'])")
      ok "merchant/wallet.php 거래내역 ${items}건"
    else
      ng "merchant/wallet.php 실패"
    fi

    local spa
    spa=$(curl -sS -b "$cookie_file" "${BASE_URL}/advertiser/")
    if echo "$spa" | grep -q 'window.__LC_AUTH__'; then
      local spa_active
      spa_active=$(echo "$spa" | grep -o 'window.__LC_AUTH__=[^<]*' | head -1 | python3 -c "
import sys,re,json
line=sys.stdin.read().strip()
m=re.search(r'window.__LC_AUTH__=(.+)', line)
if not m: sys.exit(1)
d=json.loads(m.group(1))
print('yes' if d.get('loggedIn') and d.get('isActiveMerchant') else 'no')
" 2>/dev/null || echo "no")
      if [[ "$spa_active" == "yes" ]]; then ok "SPA /advertiser/ 접근 (isActiveMerchant)"; else ng "SPA /advertiser/ auth 부족"; fi
    elif echo "$spa" | grep -q 'id="root"'; then
      warn "SPA HTML 로드됐으나 __LC_AUTH__ 미주입"
    else
      ng "SPA /advertiser/ 로드 실패"
    fi
  fi

  rm -f "$cookie_file"
}

echo "LinkConnect 데모 계정 검증 — ${BASE_URL}"
echo "대기: 배포 반영 (45초)..."
sleep 45

check_account "파트너" "$PARTNER_MB"
check_account "광고주" "$ADVERTISER_MB"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━"
echo "결과: 통과 ${PASS} / 실패 ${FAIL} / 경고 ${WARN}"
if [[ "$FAIL" -gt 0 ]]; then
  exit 1
fi
