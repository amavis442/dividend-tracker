<?php

namespace App\Command;

use App\Entity\User;
use App\Service\UserManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name:'app:create-user',
    description:'Create a new user',
)]
class CreateUserCommand extends Command
{
    private $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userManager = $this->userManager;

        $helper = $this->getHelper('question');
        $questionEmail = new Question('Please enter the email: ', null);
        $questionEmail->setNormalizer(function ($value) {
            // $value can be null here
            return $value ? trim($value) : '';
        });
        $questionEmail->setValidator(function ($answer) use ($userManager) {
            $answer = trim($answer);
            if ((int) strlen($answer) < 3 || $answer == '') {
                throw new \RuntimeException(
                    'Email to short or not present.'
                );
            }

            if ($userManager->isUserDuplicate($answer)) {
                throw new \RuntimeException(
                    'Email already exists.'
                );
            }
            return $answer;
        });
        $questionEmail->setMaxAttempts(2);
        $email = $helper->ask($input, $output, $questionEmail);

        $questionPassword = new Question('Password: ');
        $questionPassword->setHidden(true);
        $questionPassword->setHiddenFallback(false);
        $questionPassword->setNormalizer(function ($value) {
            // $value can be null here
            return $value ? trim($value) : '';
        });
        $password = $helper->ask($input, $output, $questionPassword);

        $questionPasswordRepeat = new Question('Password repeat: ');
        $questionPasswordRepeat->setNormalizer(function ($value) {
            // $value can be null here
            return $value ? trim($value) : '';
        });
        $questionPasswordRepeat->setValidator(function ($passwordRepeat) use ($password) {
            if ($password !== $passwordRepeat) {
                throw new \RuntimeException(
                    'Entered passwords do not match.'
                );
            }

            return $passwordRepeat;
        });
        $questionPasswordRepeat->setHidden(true);
        $questionPasswordRepeat->setHiddenFallback(false);
        $questionPasswordRepeat->setMaxAttempts(2);
        $passwordRepeat = $helper->ask($input, $output, $questionPasswordRepeat);

        $questionRoles = new Question('Please enter roles (comma separated): ', 'user');
        $questionRoles->setNormalizer(function ($value) {
            // $value can be null here
            return $value ? strtolower(trim($value)) : '';
        });
        $questionRoles->setValidator(function ($answer) {
            $roles = explode(',', trim($answer));
            foreach ($roles as &$role) {
                if (!in_array($role, User::ROLES)) {
                    throw new \RuntimeException(
                        'Roles can be ' . implode(',', User::ROLES)
                    );
                }
                $role = 'ROLE_' . strtoupper($role);
            }
            return $roles;
        });

        $questionRoles->setMaxAttempts(2);
        $roles = $helper->ask($input, $output, $questionRoles);

        $userManager->create($email, $password, $roles);

        $io = new SymfonyStyle($input, $output);
        $io->success('New user created.');

        return Command::SUCCESS;
    }
}
