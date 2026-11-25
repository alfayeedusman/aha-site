#!/usr/bin/env bash
#
# Build the site and publish it to https://aha.yowtech.io (Hostinger, over SSH).
#
# Usage:
#   ./deploy/deploy.sh            # build, upload a new release, switch the subdomain to it
#   ./deploy/deploy.sh --rollback # point the subdomain back at the previous release
#
# Layout on the server (same pattern as finance/pamperzone/picklehabit):
#   ~/releases/aha-site-<date>-<commit>/                  one folder per deploy
#   ~/domains/yowtech.io/public_html/aha -> that folder   (the aha.yowtech.io document root)
#
# SSH details: hPanel → Advanced → SSH Access. Override with AHA_SSH_* env vars if they change.

set -euo pipefail

HOST="${AHA_SSH_HOST:-82.180.143.154}"
USER="${AHA_SSH_USER:-u442041240}"
PORT="${AHA_SSH_PORT:-65002}"
KEY="${AHA_SSH_KEY:-$HOME/.ssh/yowtobooth_hostinger}"
DOCROOT="domains/yowtech.io/public_html/aha"
SITE_URL="https://aha.yowtech.io"

# One shared SSH connection per deploy (ControlMaster): Hostinger's brute-force protection
# blocks an IP that opens many SSH connections in a short time.
CTL="/tmp/aha-deploy-%C"
SSH_OPTS="-p $PORT -i $KEY -o IdentitiesOnly=yes -o BatchMode=yes -o ConnectTimeout=20 -o ControlMaster=auto -o ControlPath=$CTL -o ControlPersist=60"
SSH=(ssh $SSH_OPTS "$USER@$HOST")
trap 'ssh $SSH_OPTS -O exit "$USER@$HOST" 2>/dev/null || true' EXIT
cd "$(dirname "${BASH_SOURCE[0]}")/.."

if [[ "${1:-}" == "--rollback" ]]; then
  "${SSH[@]}" "set -e; cd ~/releases; cur=\$(readlink ~/$DOCROOT | xargs basename);
    prev=\$(ls -d aha-site-* | sort | grep -B1 -x \"\$cur\" | head -n1);
    [ -n \"\$prev\" ] && [ \"\$prev\" != \"\$cur\" ] || { echo 'no earlier release'; exit 1; };
    ln -sfn \"\$HOME/releases/\$prev\" ~/$DOCROOT; echo \"→ now serving \$prev\""
  exit 0
fi

if [[ -n "$(git status --porcelain)" ]]; then
  echo "warning: uncommitted changes will be deployed (release is named after HEAD)." >&2
fi
REL="aha-site-$(date +%Y%m%d-%H%M)-$(git rev-parse --short HEAD)"

echo "→ building for $SITE_URL"
rm -rf dist
SITE_URL="$SITE_URL" BASE_PATH=/ npm run build >/dev/null

echo "→ uploading to ~/releases/$REL"
rsync -az --checksum -e "ssh $SSH_OPTS" dist/ "$USER@$HOST:releases/$REL/"

echo "→ switching aha.yowtech.io to $REL"
"${SSH[@]}" "set -e; test -f ~/releases/$REL/index.html; ln -sfn \"\$HOME/releases/$REL\" ~/$DOCROOT;
  cd ~/releases && ls -d aha-site-* | sort | head -n -5 | xargs -r rm -rf"   # keep the last 5 releases

echo "→ verifying"
for path in / /privacy /support.html /og.png /does-not-exist; do
  printf '   %-18s %s\n' "$path" "$(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL$path")"
done
