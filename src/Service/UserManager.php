<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Validator\StrongPassword;
use InvalidArgumentException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserManager
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    public function isUserDuplicate(string $email): bool
    {
        $result = $this->userRepository->findOneBy(['email' => $email]);

        return $result !== null;
    }

    /**
     * @return list<string> Why the email address is not accepted; empty when it is valid.
     */
    public function emailErrors(string $email): array
    {
        return $this->messages($this->validator->validate($email, [new NotBlank(), new Email()]));
    }

    /**
     * @return list<string> Why the password is not accepted; empty when it is strong enough.
     */
    public function passwordErrors(string $password): array
    {
        return $this->messages($this->validator->validate($password, new StrongPassword()));
    }

    public function create(string $email, string $password, ?array $roles = null): void
    {
        $errors = [...$this->emailErrors($email), ...$this->passwordErrors($password)];
        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $user = new User();
        $user->setPassword($this->passwordHasher->hashPassword(
            $user,
            $password
        ));
        $user->setEmail($email);
        $user->setRoles($roles);
        $this->userRepository->save($user);
    }

    /**
     * @return list<string>
     */
    private function messages(iterable $violations): array
    {
        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = (string) $violation->getMessage();
        }

        return $messages;
    }
}
