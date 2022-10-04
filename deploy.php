<?php
namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

require 'recipe/symfony4.php';

// Project name
set('application', 'dividend.banpagi.com');

// Project repository
set('repository', 'git@gitlab.com:amavis442/dividend.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);
set('keep_releases',2);
set('yarn','yarn');
set('writable_mode','acl');

// Shared files/dirs between deploys
add('shared_files', ['.env.local','public/uploads']);
//add('shared_dirs', ['public/media','public/assets','public/bundles']);

// Writable dirs by web server
//add('writable_dirs', ['public/media','public/assets','public/bundles']);
set('allow_anonymous_stats', false);

// Hosts

/*
host('134.209.84.155')
    ->stage('prod','staging')
    ->user('deployer')
    ->roles('app')
    ->port(22)
    ->configFile('~/.ssh/config')
    ->identityFile('~/.ssh/deployer')
    ->multiplexing(true)
    ->forwardAgent(true)
    ->set('deploy_path', '/var/www/{{application}}');
*/

host('192.168.2.143')
    ->stage('local','prod','staging')
    ->user('deployer')
    ->roles('app')
    ->port(22)
    ->configFile('~/.ssh/config')
    ->identityFile('~/.ssh/deployer')
    ->multiplexing(true)
    ->forwardAgent(true)
    ->set('deploy_path', '/var/www/{{application}}');

/*localhost()
    ->stage('local')
    ->roles('test', 'build')
    ->set('deploy_path', '~/Sites/live/{{application}}');
*/
// Tasks
desc('Yarn install'); // For encore and stuff
task('yarn:install', function(){
    run('cd ' . get('release_path') . ' && {{yarn}} install');
});

desc('Yarn build');
task('yarn:build', function(){
    run('cd ' . get('release_path') . ' && {{yarn}} run encore prod');
});

desc('Reload php-fpm config');
task('php-fpm:reload', function () {
    run('sudo /bin/systemctl reload php7.4-fpm');
});

desc('Runs yarn, migrates the database and install the assets');
task('deploy:dividend', [
    'database:migrate',
    'yarn:install',
    'yarn:build'
]);

after('deploy:writable', 'deploy:dividend');

// Last step after symlink has been added.
after('deploy', 'php-fpm:reload');


option('source', null, InputOption::VALUE_OPTIONAL, 'Source alias of the current task.');
option('target', null, InputOption::VALUE_OPTIONAL, 'Target alias of the current task.');

task('upload:file', function() {
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
