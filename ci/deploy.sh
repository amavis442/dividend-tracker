#!/bin/sh
# Deploys to one Deployer host from GitLab CI: ci/deploy.sh <acceptance|production>
#
# Needs two protected CI/CD variables (see DEPLOYMENT.md):
#   DEPLOY_SSH_PRIVATE_KEY  private key of the CI key pair, accepted by deployer@<server>
#   DEPLOY_SSH_KNOWN_HOSTS  output of `ssh-keyscan <server>`, so the host key is verified
# Both may be of type File (the variable holds a path) or Variable (it holds the contents).
set -eu

host="${1:?usage: ci/deploy.sh <acceptance|production>}"
: "${DEPLOY_SSH_PRIVATE_KEY:?DEPLOY_SSH_PRIVATE_KEY is not set (protected CI/CD variable)}"
: "${DEPLOY_SSH_KNOWN_HOSTS:?DEPLOY_SSH_KNOWN_HOSTS is not set (protected CI/CD variable)}"

# Writes a CI variable to a file without printing it. GitLab strips the trailing newline,
# which OpenSSH needs at the end of a key, so one is always added.
write_variable() {
    value="$1"
    target="$2"
    if [ -f "$value" ]; then
        cat "$value" > "$target"
    else
        printf '%s' "$value" > "$target"
    fi
    printf '\n' >> "$target"
}

mkdir -p ~/.ssh
chmod 700 ~/.ssh
umask 077
write_variable "$DEPLOY_SSH_PRIVATE_KEY" ~/.ssh/id_ed25519
write_variable "$DEPLOY_SSH_KNOWN_HOSTS" ~/.ssh/known_hosts
chmod 600 ~/.ssh/id_ed25519
chmod 644 ~/.ssh/known_hosts

php vendor/bin/dep deploy "$host" --no-interaction -v
