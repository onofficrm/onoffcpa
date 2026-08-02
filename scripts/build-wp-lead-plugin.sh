#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="$ROOT/wordpress/linkconnect-lead"
OUT_DIR="$ROOT/plugin/linkconnect/assets/wordpress"
ZIP_NAME="linkconnect-lead.zip"
TMP="$(mktemp -d)"

cleanup() { rm -rf "$TMP"; }
trap cleanup EXIT

if [[ ! -f "$SRC/linkconnect-lead.php" ]]; then
  echo "Missing plugin source: $SRC" >&2
  exit 1
fi

mkdir -p "$OUT_DIR" "$TMP/linkconnect-lead"
rsync -a --exclude '.DS_Store' --exclude '._*' "$SRC/" "$TMP/linkconnect-lead/"

(
  cd "$TMP"
  zip -qr "$OUT_DIR/$ZIP_NAME" linkconnect-lead
)

echo "Built: $OUT_DIR/$ZIP_NAME ($(wc -c < "$OUT_DIR/$ZIP_NAME") bytes)"
