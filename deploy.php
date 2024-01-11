<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

require 'recipe/symfony.php';
require 'contrib/rsync.php';
require 'contrib/npm.php';

// Project name
set('application', 'dividend.prod');

// Project repository
set('repository', 'git@gitlab.com:amavis442/dividend.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);
set('keep_releases', 2);
//set('npm', '/usr/bin/npm');
set('writable_mode', 'acl');

// Shared files/dirs between deploys
add('shared_files', ['.env.local', 'public/uploads']);
set('allow_anonymous_stats', false);

// Hosts

host('192.168.2.220')
    ->setRemoteUser('deployer')
    ->setDeployPath('/var/www/dividend.banpagi.com')
    ->setLabels([
        'type' => 'web',
        'env' => 'prod',
        'stage' => 'prod',
    ]);

host('127.0.0.1')
    ->setRemoteUser('deployer')
    ->setDeployPath('/var/www/{{application}}')
    ->setLabels([
        'type' => 'local',
        'env' => 'prod',
        'stage' => 'prod',
    ])
    ->set('branch', 'postgres')
    ->set('rsync_src', __DIR__)
    ->set('rsync_dest', '{{release_path}}');


set('rsync', [
    'exclude'      => [
        '.git',
        'deploy.php',
    ],
    'exclude-file' => false,
    'include'      => [],
    'include-file' => false,
    'filter'       => [],
    'filter-file'  => false,
    'filter-perdir' => false,
    'flags'        => 'rz', // Recursive, with compress
    'options'      => ['delete'],
    'timeout'      => 60,
]);

// Tasks
set('bin/npm', function () {
    return '/usr/bin/node';
});
after('deploy:update_code', 'npm:install');
/*
desc('NPM install'); // For encore and stuff
task('npm:install', function () {
    run('cd ' . get('release_path') . ' && {{npm}} install');
});

desc('NPM build');
task('npm:build', function () {
    run('cd ' . get('release_path') . ' && {{npm}} run build');
});


desc('Reload php-fpm config');
task('php-fpm:reload', function () {
    run('sudo /bin/systemctl reload php8.2-fpm');
});
*/

desc('Runs npm, migrates the database and install the assets');
task('deploy:dividend', [
    'database:migrate'
]);

after('deploy:vendors', 'deploy:dividend');

// Last step after symlink has been added.
//after('deploy', 'php-fpm:reload');


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
