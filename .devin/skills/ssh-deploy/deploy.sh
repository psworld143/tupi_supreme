#!/usr/bin/env bash
# deploy.sh — Deploy tupi_supreme to the production VPS.
#
# Workflow (see SKILL.md):
#   1. Fetch + fast-forward local repo from origin
#   2. Local build check: php -l on every PHP file
#   3. Deploy: tar stream -> SSH -> server-side rsync --delete into web root
#   4. Smoke test the live site over HTTPS
#
# Auth: key-based (~/.ssh/tupi_deploy). No password is stored or required.
# Override any setting via env vars, e.g. SSH_HOST=... ./deploy.sh
set -euo pipefail
export MSYS_NO_PATHCONV=1   # stop Git Bash from mangling remote paths

SSH_HOST="${SSH_HOST:-85.31.232.90}"
SSH_PORT="${SSH_PORT:-22}"
SSH_USER="${SSH_USER:-root}"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/tupi_deploy}"
DEPLOY_PATH="${DEPLOY_PATH:-/home/tupisupreme.com/public_html}"
SITE_URL="${SITE_URL:-https://tupisupreme.com}"
OWNER="${OWNER:-tupis9290:tupis9290}"   # cPanel account that must own the files

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$REPO_ROOT"

SSH_OPTS=(-i "$SSH_KEY" -p "$SSH_PORT" -o BatchMode=yes -o ConnectTimeout=15)
SSH_DEST="$SSH_USER@$SSH_HOST"

step() { printf '\n\033[1;34m== %s ==\033[0m\n' "$*"; }

# ---------------------------------------------------------------- 1. sync ----
step "1/4 Fetch & sync from origin"
git fetch origin
git pull --ff-only
if git status -sb | grep -q 'ahead'; then
  echo "WARNING: local commits not pushed to origin — they WILL be deployed."
fi
git log --oneline -1

# --------------------------------------------------------------- 2. build ----
step "2/4 Lint PHP"
PHP_BIN="$(command -v php || true)"
[ -z "$PHP_BIN" ] && PHP_BIN="/c/wamp64/bin/php/php8.3.28/php.exe"
FAIL=0
while IFS= read -r -d '' f; do
  "$PHP_BIN" -l "$f" >/dev/null || { echo "SYNTAX ERROR: $f"; FAIL=1; }
done < <(find . -name '*.php' -not -path './.git/*' -print0)
[ "$FAIL" -eq 0 ] || { echo "Lint failed — aborting deploy."; exit 1; }
echo "All PHP files OK ($("$PHP_BIN" -v | head -1))"

# -------------------------------------------------------------- 3. deploy ----
step "3/4 Deploy to $SSH_DEST:$DEPLOY_PATH"
STAGING="/tmp/tupi_deploy_$$"
tar czf - \
  --exclude='./.git' --exclude='./.github' --exclude='./.devin' \
  --exclude='./documentation_backup' --exclude='./database' \
  --exclude='./uploads' --exclude='./sessions' \
  --exclude='*.sql' --exclude='*.zip' \
  --exclude='config.php' --exclude='db_credentials.php' \
  --exclude='mail_config.php' --exclude='mail.ini' \
  --exclude='upload_diag.php' \
  . | ssh "${SSH_OPTS[@]}" "$SSH_DEST" "
    set -e
    rm -rf '$STAGING' && mkdir -p '$STAGING'
    tar xzf - -C '$STAGING'
    rsync -a --delete --chown='$OWNER' \
      --exclude '/uploads/' --exclude '/sessions/' \
      --exclude '/1.backup/' --exclude '/database/' \
      --exclude '*.sql' --exclude '*.zip' \
      --exclude 'config.php' --exclude 'db_credentials.php' \
      --exclude 'mail_config.php' --exclude 'mail.ini' \
      '$STAGING/' '$DEPLOY_PATH/'
    mkdir -p '$DEPLOY_PATH/uploads/images' '$DEPLOY_PATH/uploads/documents' '$DEPLOY_PATH/sessions'
    chown -R '$OWNER' '$DEPLOY_PATH/uploads' '$DEPLOY_PATH/sessions'
    chmod -R 0777 '$DEPLOY_PATH/uploads' '$DEPLOY_PATH/sessions'
    find '$DEPLOY_PATH' -maxdepth 1 -name '*.php' -exec php -l {} \; >/dev/null && echo 'REMOTE_LINT_OK'
    rm -rf '$STAGING'
  "

# uploads/.htaccess is excluded from rsync — push it directly so it exists.
for d in uploads sessions; do
  if [ -f "$d/.htaccess" ]; then
    scp -i "$SSH_KEY" -P "$SSH_PORT" -o BatchMode=yes "$d/.htaccess" "$SSH_DEST:$DEPLOY_PATH/$d/.htaccess" >/dev/null
  fi
done

# ------------------------------------------------------------ 4. verify ------
step "4/4 Smoke test $SITE_URL"
FAIL=0
for p in / /about.php /products.php /admin/login.php; do
  code=$(curl -sk -o /dev/null -w '%{http_code}' --http1.1 --max-time 15 "$SITE_URL$p" || true)
  code="${code:-000}"
  if [ "$code" = "200" ]; then echo "  OK   $p ($code)"; else echo "  FAIL $p ($code)"; FAIL=1; fi
done
[ "$FAIL" -eq 0 ] && echo -e "\nDeploy complete." || { echo -e "\nDeploy finished with smoke-test failures."; exit 1; }
