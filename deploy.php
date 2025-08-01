<?php

namespace Deployer;

require 'recipe/symfony.php';
require 'contrib/cachetool.php';

// Config

set('application', 'dividend');
set('repository', 'git@gitlab.com:amavis442/dividend.git');
set('git_tty', true);
set('keep_releases', 5);
set('writable_mode', 'acl');
set('allow_anonymous_stats', false);
set('bin/npm', function () {
    return run('which npm');
});
set('cachetool', '/run/php/php8.3-fpm-dividend.sock');
set('bin/cachetool', function () {
    return which('cachetool.phar');
});


// Shared files/dirs between deploys
add('shared_files', ['.env.local', 'public/uploads']);

// Hosts
host('prod')
    ->set('hostname', '127.0.0.1')
    ->set('branch' ,'main')
    ->setRemoteUser('deployer')
    ->setDeployPath('/var/www/prod/{{application}}')
    ->setLabels([
        'type' => 'local',
        'env' => 'prod',
        'stage' => 'prod',
    ])
    ->set('cachetool', '/run/php/php8.3-fpm-dividend.sock');

host('acc')
    ->set('hostname', '127.0.0.1')
    ->set('branch' ,'main')
    ->setRemoteUser('deployer')
    ->setDeployPath('/var/www/acc/{{application}}')
    ->setLabels([
        'type' => 'local',
        'env' => 'prod',
        'stage' => 'acc',
    ])
    ->set('cachetool', '/run/php/php8.3-fpm-prod-sites.sock');

host('acerdeploy')
    ->set('hostname', 'acerdeploy')
    ->set('branch' ,'main')
    ->setRemoteUser('deployer')
    ->setDeployPath('/var/www/prod/{{application}}')
    ->setLabels([
        'type' => 'local',
        'env' => 'prod',
        'stage' => 'prod',
    ]);

host('proxmox')
    ->set('hostname', 'proxmox')
    ->set('branch' ,'main')
    ->setRemoteUser('deployer')
    ->setDeployPath('/var/www/prod/{{application}}')
    ->setLabels([
        'type' => 'local',
        'env' => 'prod',
        'stage' => 'prod',
    ]);

host('test')
    ->set('hostname', '127.0.0.1')
    ->set('branch' ,'main')
    ->setRemoteUser('deployer')
    ->setDeployPath('/var/www/test/{{application}}')
    ->setLabels([
        'type' => 'local',
        'env' => 'test',
        'stage' => 'test',
    ]);

// Tasks
desc('Install npm packages');
task('npm:install', function () {
    if (has('previous_release')) {
        if (test('[ -d {{previous_release}}/node_modules ]')) {
            run('cp -R {{previous_release}}/node_modules {{release_path}}');

            // If package.json is unmodified, then skip running `npm install`
            if (!run('diff {{previous_release}}/package.json {{release_path}}/package.json')) {
                return;
            }
        }
    }
    run("cd {{release_path}} && {{bin/npm}} install");
});

desc('Install npm packages with a clean slate');
task('npm:ci', function () {
    run("cd {{release_path}} && {{bin/npm}} ci");
});

desc('Dumps composer autoloader');
task("composer:dump", function () {
    run('cd {{release_or_current_path}} && composer dump-autoload --no-dev --classmap-authoritative');
});

desc('Add assets with assetmapper');
task('assetmap:compile', function () {
    run('{{bin/console}} asset-map:compile');
});

task('info', function () {
    writeln('type:' . get('labels')['type'] . ' env:' . get('labels')['env']);
});

task('tailwind:build', function () {
    run('{{bin/console}} tailwind:build');
});

task('ci:run', function () {
    runLocally('php vendor/bin/phpstan analyse src/ -c phpstan.neon --level=5 --no-progress -vvv --memory-limit=1024M');
    runLocally('SYMFONY_DEPRECATIONS_HELPER=disabled XDEBUG_MODE=coverage php bin/phpunit --do-not-fail-on-warning --do-not-fail-on-risky --coverage-html var/coverage');
});

// Hooks
after('deploy:cache:clear', 'database:migrate');
after('database:migrate', 'tailwind:build');
after('tailwind:build', 'assetmap:compile');
after('deploy:failed', 'deploy:unlock');
after('deploy:symlink', 'cachetool:clear:opcache');
