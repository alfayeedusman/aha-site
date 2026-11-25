#!/usr/bin/env bash
# Set (or change) the password for https://aha.yowtech.io/admin.
# The password is sent over SSH, hashed on the server with PHP's password_hash and stored in
# ~/aha-analytics/admin.hash (outside the web root). It is never written to this repo.
set -euo pipefail
HOST=82.180.143.154; PORT=65002; USER=u442041240; KEY="$HOME/.ssh/yowtobooth_hostinger"

read -rsp "New admin password (12+ characters): " PW; echo
read -rsp "Repeat it: " PW2; echo
[ "$PW" = "$PW2" ] || { echo "Passwords don't match."; exit 1; }
[ ${#PW} -ge 12 ] || { echo "Use at least 12 characters."; exit 1; }

printf '%s' "$PW" | ssh -p "$PORT" -i "$KEY" -o IdentitiesOnly=yes "$USER@$HOST" \
  'mkdir -p ~/aha-analytics && chmod 700 ~/aha-analytics && umask 077 &&
   php -r '\''echo password_hash(stream_get_contents(STDIN), PASSWORD_DEFAULT);'\'' > ~/aha-analytics/admin.hash.new &&
   mv ~/aha-analytics/admin.hash.new ~/aha-analytics/admin.hash && echo "Password saved."'
unset PW PW2
echo "Sign in at https://aha.yowtech.io/admin/"
