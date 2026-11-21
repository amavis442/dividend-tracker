# Deployment

Deploys run with Deployer (`deploy.php`), either from GitLab (recommended) or from a
workstation with `vendor/bin/dep deploy <acceptance|production>`. Every deploy already does
the following, so these need no manual step:

- code update (over HTTPS from the public GitLab repository), `composer install`, `cache:clear`
- `doctrine:migrations:migrate` (task `database:migrate`)
- `tailwind:build` and `asset-map:compile`
- shared between releases: `.env.local`, `public/uploads`, `var/uploads`, `var/log`, `var/cache`

## Deploying from GitLab

A pipeline on `main` shows two manual jobs in the `deployment` stage, available once
`static_analysis` and `phpunit` passed:

1. **deploy:acceptance** — press play to deploy this commit to acceptance.
2. **deploy:production** — becomes available after deploy:acceptance succeeded in the same pipeline.

Each environment runs one deploy at a time, and a running deploy is never cancelled. GitLab lists
the deploys under *Operate → Environments*.

### One-time setup

The jobs log in as `deployer` on 192.168.2.49 with a key pair used only by GitLab CI.

1. Create the key pair on your workstation (no passphrase, CI cannot type one):

   ```bash
   ssh-keygen -t ed25519 -N '' -C 'gitlab-ci dividend deploy' -f ./dividend-ci-deploy
   ```

2. Allow it on the server:

   ```bash
   ssh-copy-id -i ./dividend-ci-deploy.pub deployer@192.168.2.49
   ```

3. Collect the server's host key, so CI verifies it connects to the right machine:

   ```bash
   ssh-keyscan -t ed25519,rsa 192.168.2.49 > ./dividend-known-hosts
   ```

4. In GitLab: *Settings → CI/CD → Variables → Add variable*, twice:

   | Key | Type | Value | Flags |
   |---|---|---|---|
   | `DEPLOY_SSH_PRIVATE_KEY` | File | contents of `dividend-ci-deploy` (the private key, ending with a newline) | Protected |
   | `DEPLOY_SSH_KNOWN_HOSTS` | File | contents of `dividend-known-hosts` | Protected |

   GitLab cannot mask a multi-line value; the deploy script never prints it.

   *Protected* means the variables only reach pipelines on protected branches. `main` must be a
   protected branch (*Settings → Repository → Protected branches*), which it is by default.

5. Delete the local copies of the private key once it is stored in GitLab
   (`rm dividend-ci-deploy`), or keep it only in your password manager.

To revoke CI access, remove the key's line from `~deployer/.ssh/authorized_keys` on the server.

### First deploy after switching to GitLab

`deploy.php` now fetches the repository over HTTPS instead of SSH. Deployer notices the changed
URL and re-creates its cached copy (`.dep/repo`) once; that first deploy takes a little longer.

---

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
