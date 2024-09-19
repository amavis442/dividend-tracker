php bin/console cache:clear --env=test
php bin/console doctrine:database:drop --if-exists --force --env=test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:update --complete --force --env=test
php bin/phpunit
