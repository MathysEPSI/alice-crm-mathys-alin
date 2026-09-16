<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManager
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private SlugService $slugService
    ) {
    }

    /** Indique si un compte utilise déjà cette adresse email. */
    public function emailExists(?string $email): bool
    {
        return null !== $email && null !== $this->userRepository->findOneBy(['email' => $email]);
    }

    /** Hash le mot de passe saisi, génère le slug et persiste le nouvel utilisateur. */
    public function createUser(User $user): User
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
        $user->setSlug($this->slugService->slugifyFullname($user->getFirstname(), $user->getLastname()));

        $this->userRepository->save($user, true);

        return $user;
    }
}
