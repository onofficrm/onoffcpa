#!/usr/bin/env bash
# LinkConnect DB 원격 설치 (서버에 linkconnect_install_token 설정 필요)
#
# Usage:
#   LC_INSTALL_TOKEN=your_token ./scripts/remote-install-linkconnect.sh
#   ./scripts/remote-install-linkconnect.sh https://linkconnect.co.kr your_token admin
set -euo pipefail

BASE_URL="${1:-https://linkconnect.co.kr}"
TOKEN="${2:-${LC_INSTALL_TOKEN:-}}"
PARTNER_MB="${3:-admin}"
MERCHANT_MB="${4:-admin}"

if [[ -z "$TOKEN" ]]; then
  echo "Usage: LC_INSTALL_TOKEN=token $0 [base_url] [token] [partner_mb_id] [merchant_mb_id]" >&2
  exit 1
fi

URL="${BASE_URL%/}/plugin/linkconnect/install/install.php"
echo "POST $URL"

curl -fsS -X POST "$URL" \
  -d "action=run" \
  -d "token=${TOKEN}" \
  -d "activate_mb_id=${PARTNER_MB}" \
  -d "activate_merchant_mb_id=${MERCHANT_MB}"

echo ""
