<?php

namespace Deployer;

require 'recipe/symfony.php';

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

// Shared files/dirs between deploys
add('shared_files', ['.env.local', 'public/uploads']);

// Hosts
host('127.0.0.1')
    ->setRemoteUser('deployer')
    ->setDeployPath('/var/www/prod/{{application}}')
    ->setLabels([
        'type' => 'local',
        'env' => 'prod',
        'stage' => 'prod',
    ]);


host('127.0.0.1')
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

// Hooks
after('deploy:cache:clear', 'database:migrate');
after('database:migrate', 'assetmap:compile');
after('deploy:failed', 'deploy:unlock');
