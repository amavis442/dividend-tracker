php bin/console cache:clear --env=test
php bin/console doctrine:database:drop --if-exists --force --env=test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:update --force --env=test
# You can check last exit code with > echo $?
# grep -q exits with 0 if value is found.
#php vendor/bin/phpunit --testdox --debug --stop-on-failure --stop-on-error | grep -q 'OK' | echo $?
php vendor/bin/phpunit --testdox --debug --stop-on-failure --stop-on-error
echo $?
