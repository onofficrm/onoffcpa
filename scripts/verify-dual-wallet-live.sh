#!/usr/bin/env bash
# 운영 이중 지갑 스모크 + 잔액 불변식 점검 (비파괴)
#
# Usage:
#   ./scripts/verify-dual-wallet-live.sh
#   MP_OUTBOUND_TOKEN=... ./scripts/verify-dual-wallet-live.sh
#
# 검사:
#  1) 양쪽 health (platform code)
#  2) wallet_balance.php 양방향 인증/응답
#  3) (선택) 임시 runner 가 있으면 audit JSON 출력 — 없으면 API만
set -euo pipefail

ONOFF="${ONOFFCPA_URL:-https://onoffcpa.icrm.co.kr}"
LC="${LINKCONNECT_URL:-https://linkconnect.co.kr}"
# seed_personal_rehab_mp.php 와 동일 — 운영 시딩 토큰
TOKEN="${MP_OUTBOUND_TOKEN:-mp_out_onoff_lc_2026_rehab_k9f3Qw8Zp2}"

pass=0
fail=0
check() {
  local name="$1" cond="$2" detail="${3:-}"
  if [[ "$cond" == "1" ]]; then
    echo "[PASS] $name ${detail:+— $detail}"
    pass=$((pass + 1))
  else
    echo "[FAIL] $name ${detail:+— $detail}"
    fail=$((fail + 1))
  fi
}

json_field() {
  python3 -c 'import json,sys; d=json.load(sys.stdin); print(d'"$1"')' 2>/dev/null || true
}

echo "=== dual-wallet live smoke ==="
echo "ONOFF=$ONOFF"
echo "LC=$LC"

# 1) health
h_on=$(curl -sS --max-time 15 "$ONOFF/plugin/linkconnect/api/platform/health.php" || echo '{}')
h_lc=$(curl -sS --max-time 15 "$LC/plugin/linkconnect/api/platform/health.php" || echo '{}')
echo "health ONOFF: $h_on"
echo "health LC:    $h_lc"
p_on=$(printf '%s' "$h_on" | json_field "['data']['localPlatform']")
p_lc=$(printf '%s' "$h_lc" | json_field "['data']['localPlatform']")
check "ONOFFCPA health platform" "$([[ "$p_on" == "ONOFFCPA" ]] && echo 1 || echo 0)" "$p_on"
check "LINKCONNECT health platform" "$([[ "$p_lc" == "LINKCONNECT" ]] && echo 1 || echo 0)" "$p_lc"

# 2) peer 잔액 API (remote_status wallet_balance 명령 — 기존 파일 경로)
code_wb_on=$(curl -sS -o /tmp/wb_on.json -w '%{http_code}' --max-time 15 \
  -X POST -H 'Content-Type: application/json' \
  -H "X-LC-Platform-Token: $TOKEN" -H 'X-LC-Platform-Code: LINKCONNECT' \
  -d '{"command":"wallet_balance","sourcePlatform":"LINKCONNECT","mtId":1}' \
  "$ONOFF/plugin/linkconnect/api/platform/remote_status.php" || echo 000)
code_wb_lc=$(curl -sS -o /tmp/wb_lc.json -w '%{http_code}' --max-time 15 \
  -X POST -H 'Content-Type: application/json' \
  -H "X-LC-Platform-Token: $TOKEN" -H 'X-LC-Platform-Code: ONOFFCPA' \
  -d '{"command":"wallet_balance","sourcePlatform":"ONOFFCPA","mtId":1}' \
  "$LC/plugin/linkconnect/api/platform/remote_status.php" || echo 000)
echo "wallet via remote_status ONOFF HTTP $code_wb_on body=$(head -c 200 /tmp/wb_on.json 2>/dev/null || true)"
echo "wallet via remote_status LC    HTTP $code_wb_lc body=$(head -c 200 /tmp/wb_lc.json 2>/dev/null || true)"
check "ONOFFCPA wallet_balance command" "$([[ "$code_wb_on" == "200" || "$code_wb_on" == "401" || "$code_wb_on" == "404" ]] && [[ "$code_wb_on" != "000" ]] && echo 1 || echo 0)" "http=$code_wb_on"
check "LINKCONNECT wallet_balance command" "$([[ "$code_wb_lc" == "200" || "$code_wb_lc" == "401" || "$code_wb_lc" == "404" ]] && [[ "$code_wb_lc" != "000" ]] && echo 1 || echo 0)" "http=$code_wb_lc"

if [[ "$code_wb_on" == "401" ]]; then
  check "ONOFFCPA accepts peer token" "0" "401 — run seed_mp on ONOFFCPA"
elif [[ "$code_wb_on" == "200" ]]; then
  check "ONOFFCPA peer token ok" "1" "http=$code_wb_on"
elif [[ "$code_wb_on" == "404" ]]; then
  # merchant not found is still OK for path/auth
  check "ONOFFCPA peer token ok" "1" "merchant 404 after auth"
fi
if [[ "$code_wb_lc" == "401" ]]; then
  check "LINKCONNECT accepts peer token" "0" "401 — run seed_mp on LINKCONNECT"
elif [[ "$code_wb_lc" == "200" ]]; then
  check "LINKCONNECT peer token ok" "1" "http=$code_wb_lc"
elif [[ "$code_wb_lc" == "404" ]]; then
  check "LINKCONNECT peer token ok" "1" "merchant 404 after auth"
fi

# 3) optional audit endpoints if left by deploy workflow
for pair in "ONOFF:$ONOFF" "LC:$LC"; do
  name="${pair%%:*}"
  base="${pair#*:}"
  code=$(curl -sS -o "/tmp/audit_${name}.json" -w '%{http_code}' --max-time 20 \
    "${base}/audit_dual_wallet.php" || echo 000)
  if [[ "$code" == "200" ]]; then
    echo "audit $name: $(head -c 400 /tmp/audit_${name}.json)"
    viol=$(python3 -c "import json;d=json.load(open('/tmp/audit_${name}.json'));print(d.get('violations',0))" 2>/dev/null || echo '?')
    check "audit $name no violations" "$([[ "$viol" == "0" ]] && echo 1 || echo 0)" "violations=$viol"
  else
    echo "(skip audit $name — http $code; deploy workflow creates temporary runner)"
  fi
done

echo "========================================"
echo "PASS $pass / FAIL $fail"
exit "$fail"
