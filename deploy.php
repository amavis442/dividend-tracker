<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

require 'recipe/symfony.php';
require 'contrib/rsync.php';

// Project name
set('application', 'dividend');

// Project repository
set('repository', 'git@gitlab.com:amavis442/dividend.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);
set('keep_releases', 2);
set('writable_mode', 'acl');

// Shared files/dirs between deploys
add('shared_files', ['.env.local', 'public/uploads']);
set('allow_anonymous_stats', false);

// Hosts

/*host('192.168.2.220')
    ->setRemoteUser('deployer')
    ->setDeployPath('/var/www/prod/dividend')
    ->setLabels([
        'type' => 'web',
        'env' => 'prod',
        'stage' => 'prod',
    ]);
*/
host('127.0.0.1')
    ->setRemoteUser('deployer')
    ->setDeployPath('/var/www/prod/{{application}}')
    ->setLabels([
        'type' => 'local',
        'env' => 'prod',
        'stage' => 'prod',
    ]);
//->set('branch', 'master')
//->set('rsync_src', __DIR__)
//->set('rsync_dest', '{{release_path}}');


set('rsync', [
    'exclude' => [
        '.git',
        'deploy.php',
    ],
    'exclude-file' => false,
    'include' => [],
    'include-file' => false,
    'filter' => [],
    'filter-file' => false,
    'filter-perdir' => false,
    'flags' => 'rz', // Recursive, with compress
    'options' => ['delete'],
    'timeout' => 60,
]);

set('nvm', 'source $HOME/.nvm/nvm.sh');
set('use_nvm', function () {
    return '{{nvm}} && node --version && nvm use 20';
});

// Tasks
desc('Dumps composer autoloader');
task("composer:dump", function () {
    run('cd ' . get('release_path') . " && composer dump-autoload --no-dev --classmap-authoritative");
});

desc('Migrates the database and install the assets');
task('deploy:migrate_db_dividend', [
    'database:migrate'
]);

desc('NPM install'); // For encore and stuff
task('npm:install', function () {
    run('{{use_nvm}} && cd ' . get('release_path') . ' && npm ci');
});

desc('NPM build');
task('npm:build', function () {
    run('{{use_nvm}} && cd ' . get('release_path') . ' && npm run build');
});

option('source', null, InputOption::VALUE_OPTIONAL, 'Source alias of the current task.');
option('target', null, InputOption::VALUE_OPTIONAL, 'Target alias of the current task.');
task('upload:file', function () {
    /*
     * Usage: dep upload:file --source="some_destination/file.txt" --target="some_destination/" host
     */

    $source = null;
    $target = null;

    if (input()->hasOption('source')) {
        $source = input()->getOption('source');
    }

    if (input()->hasOption('target')) {
        $target = input()->getOption('target');
    }
    if (askConfirmation('Upload file ' . $source . ' to release/' . $target . ' ?')) {
        upload('/< some place >' . $source, '{{release_path}}/' . $target);
    }
});


after('deploy:update_code', 'deploy:vendors');
after('deploy:vendors', 'composer:dump');
after('composer:dump', 'deploy:migrate_db_dividend');
after('deploy:migrate_db_dividend', 'npm:install');
after('npm:install', 'npm:build');
