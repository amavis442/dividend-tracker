#!/bin/sh
# Stop at the first failing command, so a failing test run fails the CI job.
set -e

php bin/console cache:clear --env=test
php bin/console doctrine:database:drop --if-exists --force --env=test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:update --force --env=test

# Coverage is collected with PCOV (installed by ci/php-install.sh).
php vendor/bin/phpunit --testdox
