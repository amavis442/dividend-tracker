# Deployment

Deploys run with Deployer (`vendor/bin/dep deploy <host>`, see `deploy.php`). Every deploy
already does the following, so these need no manual step:

- `composer install`, `cache:clear`
- `doctrine:migrations:migrate` (task `database:migrate`)
- `tailwind:build` and `asset-map:compile`
- shared between releases: `.env.local`, `public/uploads`, `var/uploads`, `var/log`, `var/cache`

Some releases need a one-time action on the server. They are listed below, newest first.
Tick them off per environment (acceptance, production) once done.

---

## Release with #77: login throttling, password rules, encrypted API keys

**Before the deploy**

1. Generate an encryption key and add it to `.env.local` on the server:

   ```bash
   php -r 'echo base64_encode(random_bytes(32)), PHP_EOL;'
   ```

   ```dotenv
   APP_ENCRYPTION_KEY=<the generated key>
   ```

2. **Keep a backup of this key** outside the server (for example in your password manager).
   Without it the stored API keys cannot be decrypted; they would have to be entered again.

**After the deploy** (the migration that widens the `api_key` column runs automatically)

3. Encrypt the API keys that are still stored in plain text:

   ```bash
   php bin/console app:api-keys:encrypt
   ```

   Running it again does nothing ("Encrypted 0 of N").

**Check**

- Log in works; after 5 wrong passwords within 15 minutes login is refused for that user and IP.
- The Trading212 commands (for example `trading212:positions`) still work with the stored key.

| Environment | Key in `.env.local` + backup | `app:api-keys:encrypt` |
|---|---|---|
| Local (DDEV) | already in `.env.dev` | [ ] |
| Acceptance | [ ] | [ ] |
| Production | [ ] | [ ] |

In DDEV the migration does not run by itself:

```bash
ddev exec php bin/console doctrine:migrations:migrate --no-interaction
ddev exec php bin/console app:api-keys:encrypt
```

---

## Release with #75: research attachments outside `public/`

**After the deploy**

1. Move the existing attachments from `public/uploads/documents` to `var/uploads/documents`:

   ```bash
   php bin/console app:research:move-attachments
   ```

   Until this runs, existing attachments return 404. Running it again does nothing.

| Environment | `app:research:move-attachments` |
|---|---|
| Acceptance | [ ] |
| Production | [ ] |

---

## Release with #93: IncomesShares removed

No manual step. The migration drops the tables `incomes_shares_data`, `incomes_shares_data_set`
and `files` (the data is deleted on purpose). If `INCOMESSHARES` is still in `.env.local`,
it can be removed.

## Release with #74: owners for monthly summaries, import records and pie data

No manual step. The migration assigns existing rows to user 2 (or to the owner of the linked pie).
