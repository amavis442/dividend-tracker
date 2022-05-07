<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManager
{
    private $userRepository;
    private $passwordHasher;

    public function __construct(UserRepository $userRepository, UserPasswordHasherInterface  $passwordHasher)
    {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    public function isUserDuplicate(string $email)
    {
        $result = $this->userRepository->findOneBy(['email' => $email]);

        return $result !== null;
    }

    public function create(string $email, string $password, ?array $roles = null): void
    {
        $user = new User();
        $user->setPassword($this->passwordHasher->hashPassword(
            $user,
            $password
        ));
        $user->setEmail($email);
        $user->setRoles($roles);
        $this->userRepository->save($user);
    }
}
