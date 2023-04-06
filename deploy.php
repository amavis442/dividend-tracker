<?php
namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

require 'recipe/symfony.php';

// Project name
set('application', 'dividend.banpagi.com');

// Project repository
set('repository', 'git@gitlab.com:amavis442/dividend.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);
set('keep_releases',2);
set('npm','npm');
set('writable_mode','acl');

// Shared files/dirs between deploys
add('shared_files', ['.env.local','public/uploads']);
set('allow_anonymous_stats', false);

// Hosts

host('192.168.2.220')
    ->setRemoteUser('deployer')
    ->setDeployPath('/var/www/{{application}}');

// Tasks
desc('NPM install'); // For encore and stuff
task('npm:install', function(){
    run('cd ' . get('release_path') . ' && {{npm}} install');
});

desc('NPM build');
task('npm:build', function(){
    run('cd ' . get('release_path') . ' && {{npm}} run build');
});

desc('Reload php-fpm config');
task('php-fpm:reload', function () {
    run('sudo /bin/systemctl reload php7.4-fpm');
});

desc('Runs npm, migrates the database and install the assets');
task('deploy:dividend', [
    'database:migrate',
    'npm:install',
    'npm:build'
]);

after('deploy:vendors', 'deploy:dividend');

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
