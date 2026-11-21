#!/bin/sh
# Deploys to one Deployer host from GitLab CI: ci/deploy.sh <acceptance|production>
#
# Needs two protected CI/CD variables of type File (see DEPLOYMENT.md):
#   DEPLOY_SSH_PRIVATE_KEY  private key of the CI key pair, accepted by deployer@<server>
#   DEPLOY_SSH_KNOWN_HOSTS  output of `ssh-keyscan <server>`, so the host key is verified
set -eu

host="${1:?usage: ci/deploy.sh <acceptance|production>}"
: "${DEPLOY_SSH_PRIVATE_KEY:?DEPLOY_SSH_PRIVATE_KEY is not set (protected File variable)}"
: "${DEPLOY_SSH_KNOWN_HOSTS:?DEPLOY_SSH_KNOWN_HOSTS is not set (protected File variable)}"

mkdir -p ~/.ssh
chmod 700 ~/.ssh
cp "$DEPLOY_SSH_PRIVATE_KEY" ~/.ssh/id_ed25519
chmod 600 ~/.ssh/id_ed25519
cp "$DEPLOY_SSH_KNOWN_HOSTS" ~/.ssh/known_hosts
chmod 644 ~/.ssh/known_hosts

php vendor/bin/dep deploy "$host" --no-interaction -v
