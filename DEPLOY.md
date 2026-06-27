# Deploying to staging (Plesk)

Staging URL: https://staging.daidokoro.my.id

Deploys run automatically via GitHub Actions when you push to the `staging` branch.
Composer and npm build on GitHub — not on Plesk.

## Server layout

```
/var/www/vhosts/daidokoro.my.id/staging.daidokoro.my.id/
├── app/
├── public/          ← Plesk document root must point here
├── vendor/
├── .env             ← lives on server only, never deployed from git
└── artisan
```

Document root (Plesk → Hosting Settings):

```
/var/www/vhosts/daidokoro.my.id/staging.daidokoro.my.id/public
```

Do not deploy into `public_html`. The old cPanel copy-public-folder workflow is not used.

## GitHub secrets

Repo → Settings → Secrets and variables → Actions → New repository secret

| Secret | Value |
|--------|--------|
| `SSH_HOST` | `210.79.190.251` |
| `SSH_USER` | `zdmtzldw` |
| `SSH_PASSWORD` | Your SSH password (store here only, never in git) |
| `DEPLOY_PATH` | `/var/www/vhosts/daidokoro.my.id/staging.daidokoro.my.id` |

## First-time server setup

1. Change Plesk document root to `.../public` (see above).
2. Create `.env` on the server at `DEPLOY_PATH/.env` (copy from `.env.example`).
3. Set at minimum:

   ```
   APP_ENV=staging
   APP_DEBUG=false
   APP_URL=https://staging.daidokoro.my.id
   APP_KEY=           # run: php artisan key:generate --show
   ```

4. Configure your database credentials in `.env`.
5. Disable the Plesk Git deployment script (Composer/npm on Plesk is not used).
6. Seed sample data once (SSH):

   ```bash
   cd /var/www/vhosts/daidokoro.my.id/staging.daidokoro.my.id
   /opt/plesk/php/8.3/bin/php artisan db:seed --force
   ```

   Deploy runs `migrate --force` only — it does not wipe or re-seed the database on every push.

## Day-to-day workflow

```bash
git checkout staging
git merge main        # or commit directly on staging
git push origin staging
```

Then check the **Actions** tab in GitHub. A successful run deploys the app automatically.

## Troubleshooting

- **500 error after deploy:** Check `storage/logs/laravel.log` on the server.
- **Permission errors:** Ensure `storage/` and `bootstrap/cache/` are writable (775).
- **Assets missing:** Confirm `public/build/` exists on the server after deploy.
- **Wrong branch:** Deploy workflow only runs on pushes to `staging`.
