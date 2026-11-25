#!/usr/bin/env bash
# Refresh the IP → country database used by the visit counter (DB-IP Lite, CC BY 4.0).
# DB-IP publishes a new file monthly; running this every few months is plenty.
set -euo pipefail
cd "$(dirname "$0")"
HOST=82.180.143.154; PORT=65002; USER=u442041240; KEY="$HOME/.ssh/yowtobooth_hostinger"
TMP=$(mktemp -d); trap 'rm -rf "$TMP"' EXIT
MONTH=$(date -u +%Y-%m); PREV=$(date -u -v-1m +%Y-%m 2>/dev/null || date -u -d '1 month ago' +%Y-%m)
curl -sfL -o "$TMP/c.csv.gz" "https://download.db-ip.com/free/dbip-country-lite-$MONTH.csv.gz" \
  || curl -sfL -o "$TMP/c.csv.gz" "https://download.db-ip.com/free/dbip-country-lite-$PREV.csv.gz"
python3 -I build_geo.py "$TMP/c.csv.gz" "$TMP/geo.sqlite"
rsync -az -e "ssh -p $PORT -i $KEY -o IdentitiesOnly=yes" "$TMP/geo.sqlite" "$USER@$HOST:aha-analytics/geo.sqlite.new"
ssh -p "$PORT" -i "$KEY" -o IdentitiesOnly=yes "$USER@$HOST" 'mv ~/aha-analytics/geo.sqlite.new ~/aha-analytics/geo.sqlite'
echo "Country database updated."
