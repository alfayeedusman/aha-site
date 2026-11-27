#!/usr/bin/env bash
# Connect App Store Connect sales to https://aha.yowtech.io/admin.
#
# 1. App Store Connect → Users and Access → Integrations → App Store Connect API → Team Keys → "+".
#    Name it "aha admin sales", Access: "Sales". Download the AuthKey_XXXXXXXXXX.p8 (only possible once).
# 2. Note the Issuer ID (top of that page) and the Key ID (in the key's row).
# 3. Vendor number: App Store Connect → Payments and Financial Reports (top-left, 8 digits).
# 4. Run:  deploy/set-asc-key.sh ~/Downloads/AuthKey_XXXXXXXXXX.p8 <issuer-id> <vendor-number>
#    (the Key ID is read from the file name).
#
# The key is copied over SSH to ~/aha-analytics/asc/ (outside the web root, chmod 600).
# It is never written to this repo.
set -euo pipefail
HOST=82.180.143.154; PORT=65002; USER=u442041240; KEY="$HOME/.ssh/yowtobooth_hostinger"
APP_ID=6839034222

P8=${1:?Usage: set-asc-key.sh <AuthKey_KEYID.p8> <issuer-id> <vendor-number>}
ISSUER=${2:?Issuer ID missing}
VENDOR=${3:?Vendor number missing}
[ -f "$P8" ] || { echo "No such file: $P8"; exit 1; }
grep -q 'BEGIN PRIVATE KEY' "$P8" || { echo "$P8 doesn't look like an App Store Connect .p8 key."; exit 1; }
KEY_ID=$(basename "$P8" | sed -n 's/^AuthKey_\([A-Z0-9]*\)\.p8$/\1/p')
[ -n "$KEY_ID" ] || read -rp "Key ID (10 characters): " KEY_ID
[[ "$ISSUER" =~ ^[0-9a-f-]{36}$ ]] || { echo "Issuer ID should look like 1a2b3c4d-....-............ (36 characters)."; exit 1; }
[[ "$VENDOR" =~ ^[0-9]+$ ]] || { echo "Vendor number should be digits only."; exit 1; }

CONFIG=$(printf '{"issuer":"%s","key_id":"%s","vendor":"%s","app_id":"%s"}' "$ISSUER" "$KEY_ID" "$VENDOR" "$APP_ID")
SSH=(ssh -p "$PORT" -i "$KEY" -o IdentitiesOnly=yes "$USER@$HOST")

"${SSH[@]}" 'mkdir -p ~/aha-analytics/asc && chmod 700 ~/aha-analytics ~/aha-analytics/asc'
"${SSH[@]}" 'umask 077 && cat > ~/aha-analytics/asc/AuthKey.p8.new && mv ~/aha-analytics/asc/AuthKey.p8.new ~/aha-analytics/asc/AuthKey.p8' < "$P8"
printf '%s' "$CONFIG" | "${SSH[@]}" 'umask 077 && cat > ~/aha-analytics/asc/config.json'

# Test the key and fetch the first reports right away.
"${SSH[@]}" 'cd ~/domains/yowtech.io/public_html/aha/api && php -r '\''
  require "_stats.php"; require "_asc.php";
  $err = asc_sync(20);
  if ($err) { echo "Problem: $err\n"; exit(1); }
  $n = aha_db()->query("SELECT COALESCE(SUM(units),0) FROM asc_sales WHERE substr(ptype,1,1)=\"1\"")->fetchColumn();
  echo "Connected. Paid downloads found so far: $n\n";'\'''
echo "Open https://aha.yowtech.io/admin/ to see the sales."
