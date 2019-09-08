<?php 

namespace App\Service;
use App\Repository\UserRepository;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserManager
{
    private $userRepository;
    private $passwordEncoder;

    public function __construct(UserRepository $userRepository,UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function isUserDuplicate(string $email) {
        $result = $this->userRepository->findOneBy(['email' => $email]);
        
        return $result !== null;
    }

    public function create(string $email, string $password, ?array $roles = null): void
    {
        $user = new User();
        $user->setPassword($this->passwordEncoder->encodePassword(
            $user,
            $password
        ));
        $user->setEmail($email);
        $user->setRoles($roles);
        $this->userRepository->save($user);
    }

}