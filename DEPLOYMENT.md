# Deployment

Deploys run with Deployer (`deploy.php`), either from GitLab (recommended) or from a
workstation with `vendor/bin/dep deploy <acceptance|production>`. Every deploy already does
the following, so these need no manual step:

- code update (over HTTPS from the public GitLab repository), `composer install`, `cache:clear`
- `doctrine:migrations:migrate` (task `database:migrate`)
- `tailwind:build` and `asset-map:compile`
- shared between releases: `.env.local`, `public/uploads`, `var/uploads`, `var/log` (each release has its own `var/cache`)

## Deploying from GitLab

A pipeline on `main` shows two manual jobs in the `deployment` stage, available once
`static_analysis` and `phpunit` passed:

1. **deploy:acceptance** — press play to deploy this commit to acceptance.
2. **deploy:production** — becomes available after deploy:acceptance succeeded in the same pipeline.

Each environment runs one deploy at a time, and a running deploy is never cancelled. GitLab lists
the deploys under *Operate → Environments*.

### One-time setup

The jobs log in as `deployer` on 192.168.2.49 with a key pair used only by GitLab CI.

**Where and as whom?** Create the key pair on your own workstation (for example WSL), as
yourself. Not as `gitlab-runner` and not on the runner machine: the runner uses the Docker
executor, so every job starts in a fresh container that cannot see any files of the runner
machine. The key reaches the job only through the GitLab CI/CD variable; `ci/deploy.sh` writes it
to `~/.ssh` inside the container, which is thrown away after the job.

| What | Ends up |
|---|---|
| Private key (`dividend-ci-deploy`) | only in GitLab, variable `DEPLOY_SSH_PRIVATE_KEY` |
| Public key (`dividend-ci-deploy.pub`) | on 192.168.2.49 in `~deployer/.ssh/authorized_keys` |
| Host key of the server | in GitLab, variable `DEPLOY_SSH_KNOWN_HOSTS` |

1. Create the key pair in a temporary directory (no passphrase, CI cannot type one):

   ```bash
   mkdir -p ~/tmp-ci-key && cd ~/tmp-ci-key
   ssh-keygen -t ed25519 -N '' -C 'gitlab-ci dividend deploy' -f ./dividend-ci-deploy
   ```

2. Install the public key for `deployer` on the server, depending on how you log in there:

   - If you can log in as `deployer`:

     ```bash
     ssh-copy-id -i ./dividend-ci-deploy.pub deployer@192.168.2.49
     ```

   - If you log in as your own user with sudo:

     ```bash
     scp ./dividend-ci-deploy.pub <your-user>@192.168.2.49:/tmp/
     # then, on the server:
     sudo sh -c 'cat /tmp/dividend-ci-deploy.pub >> ~deployer/.ssh/authorized_keys' && rm /tmp/dividend-ci-deploy.pub
     ```

3. Test the key; this must print `ok` without asking for a password:

   ```bash
   ssh -i ./dividend-ci-deploy -o IdentitiesOnly=yes deployer@192.168.2.49 'echo ok'
   ```

4. Collect the server's host key, so CI verifies it connects to the right machine:

   ```bash
   ssh-keyscan -t ed25519,rsa 192.168.2.49 > ./dividend-known-hosts
   ```

5. In GitLab: *Settings → CI/CD → Variables → Add variable*, once for each:

   | Key | Value (whole file, including the BEGIN/END lines) |
   |---|---|
   | `DEPLOY_SSH_PRIVATE_KEY` | `cat ./dividend-ci-deploy` |
   | `DEPLOY_SSH_KNOWN_HOSTS` | `cat ./dividend-known-hosts` |

   Settings for both: **Type** *File*, **Environments** *All*, **Visibility** *Visible*,
   **Protect variable** on, **Expand variable reference** off.

   GitLab cannot mask a multi-line value ("cannot contain whitespace characters"), so choose
   *Visible*. That is safe enough here: *Protected* keeps the variable away from other branches,
   with type *File* the variable only holds a path to a temporary file, and `ci/deploy.sh` never
   prints the contents. `main` must be a protected branch (*Settings → Repository → Protected
   branches*), which it is by default.

6. Remove the temporary directory, so the private key only exists in GitLab:

   ```bash
   cd ~ && rm -rf ~/tmp-ci-key
   ```

To revoke CI access, remove the key's line (comment `gitlab-ci dividend deploy`) from
`~deployer/.ssh/authorized_keys` on the server.

The runner machine must be able to reach 192.168.2.49 over SSH. If a deploy job times out while
connecting, check that first.

### First deploy after switching to GitLab

`deploy.php` now fetches the repository over HTTPS instead of SSH. Deployer notices the changed
URL and re-creates its cached copy (`.dep/repo`) once; that first deploy takes a little longer.

---

Some releases need a one-time action on the server. They are listed below, newest first.
Tick them off per environment (acceptance, production) once done.

---

## Release with #99: instrument table on production

No manual step. The migration creates the `instrument` table and `position.instrument_id` where
they are missing (production) and does nothing where they already exist (dev). Until it has run,
pages that load positions fail with "column p0_.instrument_id does not exist".

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
