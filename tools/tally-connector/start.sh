#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

if [[ ! -f config.json ]]; then
  cp config.example.json config.json
  echo "Created config.json — edit dms_url, connector_token, tally_url, then re-run."
  exit 1
fi

if ! command -v node >/dev/null 2>&1; then
  echo "Node.js 18+ is required."
  exit 1
fi

exec node connector.mjs
