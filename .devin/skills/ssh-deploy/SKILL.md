---
name: ssh-deploy
description: Deploy tupi_supreme to the production VPS over SSH (sync repo, lint, rsync push, smoke test). Use when the user asks to deploy, publish, push to production/VPS, or "update the live site".
---

# SSH Deploy — tupi_supreme → production VPS

Automates the standard release workflow:

1. **Fetch & sync** — `git fetch` + `git pull --ff-only` from `origin/main`
2. **Local build** — `php -l` on every PHP file (aborts on syntax errors)
3. **Deploy via SSH** — tar stream → staging dir on server → `rsync -a --delete` into the web root
4. **Test the app** — HTTP smoke test on the live domain

## Run it

```bash
bash .devin/skills/ssh-deploy/deploy.sh
```

Everything is configurable via env vars (`SSH_HOST`, `SSH_PORT`, `SSH_USER`, `SSH_KEY`, `DEPLOY_PATH`, `SITE_URL`, `OWNER`).

## Infrastructure (verified 2026-10-06)

| Item | Value |
|------|-------|
| Host | `85.31.232.90` (hostname `meet.assetlogistics.us`) |
| SSH user | `root` — key auth only, `~/.ssh/tupi_deploy` (ed25519, already authorized) |
| Web root | `/home/tupisupreme.com/public_html` — **not** `/home/tupisupreme.com` itself |
| File owner | `tupis9290:tupis9290` (cPanel account; rsync uses `--chown`) |
| Site | https://tupisupreme.com (resolves to the VPS) |
| Server PHP | 8.0.30 CLI; local lint uses WAMP PHP 8.3 |

The root password exists but is **never stored in the repo** — key auth covers all deploys.

## Deploy semantics (mirrors `.github/workflows/deploy.yml`)

- `--delete` keeps the web root an exact mirror of the repo, except protected paths:
  - `uploads/`, `sessions/` — server-generated content, never deleted/overwritten; `.htaccess` files are pushed separately after rsync
  - `1.backup/` — pre-existing backup dir on the server, protected
  - `config.php`, `db_credentials.php`, `mail_config.php`, `mail.ini` — gitignored secrets that live server-side only (basename patterns, match at any depth)
  - `*.sql`, `*.zip` — never deployed
- `uploads/` + `sessions/` are recreated and `chmod 0777` after every deploy so PHP can write to them.
- `documentation_backup/`, `database/`, `.git/`, `.github/`, `.devin/`, `upload_diag.php` are excluded from the upload entirely.
- The server also has a stale git checkout at the web root — deploys are push-based and do not use it.

## Gotchas

- Run from Git Bash (Windows). The script sets `MSYS_NO_PATHCONV=1` so remote paths aren't mangled.
- Unpushed local commits are deployed too (hotfix-friendly) — the script warns when `main` is ahead of origin.
- The GitHub Actions deploy (`.github/workflows/deploy.yml`) is the same rsync model triggered on push to `main`; this script is the local/manual equivalent.
- If key auth ever breaks, re-authorize with:
  `ssh root@85.31.232.90 "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys" < ~/.ssh/tupi_deploy.pub`
