<?php
namespace Deployer;

require 'recipe/symfony4.php';

// Project name
set('application', 'dividend.banpagi.com');

// Project repository
set('repository', 'git@gitlab.com:amavis442/dividend.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);
set('keep_releases',2);
set('yarn','yarn');
set('writable_mode','chmod');

// Shared files/dirs between deploys
add('shared_files', ['.env.local']);
//add('shared_dirs', ['public/media','public/assets','public/bundles']);

// Writable dirs by web server
//add('writable_dirs', ['public/media','public/assets','public/bundles']);
set('allow_anonymous_stats', false);

// Hosts

host('banpagi.com')
    ->stage('prod','staging')
    ->user('deployer')
    ->roles('app')
    ->port(22)
    ->configFile('~/.ssh/config')
    ->set('deploy_path', '/websites/live/{{application}}');

localhost()
    ->stage('local')
    ->roles('test', 'build')
    ->set('deploy_path', '~/Sites/live/{{application}}');

// Tasks
desc('Yarn install'); // For encore and stuff
task('yarn:install', function(){
    run('cd ' . get('release_path') . ' && {{yarn}} install');
});

desc('Yarn build');
task('yarn:build', function(){
    run('cd ' . get('release_path') . ' && {{yarn}} run encore prod');
});

//desc('Install assets');
//task('assets:install', function() {
//    run('{{release_path}}/bin/console assets:install --symlink');
//    run('{{release_path}}/bin/console sylius:theme:assets:install --symlink');
//});

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
