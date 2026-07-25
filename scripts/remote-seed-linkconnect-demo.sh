#!/usr/bin/env bash
# LinkConnect 데모 계정 원격 시드 (linkconnect_seed_token 또는 linkconnect_install_token 필요)
#
# Usage:
#   LC_SEED_TOKEN=your_token ./scripts/remote-seed-linkconnect-demo.sh
#   ./scripts/remote-seed-linkconnect-demo.sh https://linkconnect.co.kr your_token
set -euo pipefail

BASE_URL="${1:-https://linkconnect.co.kr}"
TOKEN="${2:-${LC_SEED_TOKEN:-${LC_INSTALL_TOKEN:-}}}"
PARTNER_MB="${3:-lc_partner}"
ADVERTISER_MB="${4:-lc_advertiser}"

if [[ -z "$TOKEN" ]]; then
  echo "Usage: LC_SEED_TOKEN=token $0 [base_url] [token] [partner_mb_id] [advertiser_mb_id]" >&2
  exit 1
fi

URL="${BASE_URL%/}/plugin/linkconnect/install/seed_demo.php"
echo "POST $URL"

curl -fsS -X POST "$URL" \
  -d "action=run" \
  -d "token=${TOKEN}" \
  -d "partner_mb_id=${PARTNER_MB}" \
  -d "advertiser_mb_id=${ADVERTISER_MB}"

echo ""
