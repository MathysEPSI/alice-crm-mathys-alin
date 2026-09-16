<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SlugService;
use App\Service\UserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManagerTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private UserManager $userManager;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->userManager = new UserManager($this->userRepository, $this->passwordHasher, new SlugService());
    }

    public function testEmailExistsIsTrueWhenRepositoryReturnsAUser(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(new User());

        $this->assertTrue($this->userManager->emailExists('john@test.com'));
    }

    public function testEmailExistsIsFalseWhenRepositoryReturnsNothing(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);

        $this->assertFalse($this->userManager->emailExists('john@test.com'));
    }

    public function testEmailExistsIsFalseWhenEmailIsNull(): void
    {
        $this->userRepository->expects($this->never())->method('findOneBy');

        $this->assertFalse($this->userManager->emailExists(null));
    }

    public function testCreateUserHashesPasswordSetsSlugAndPersists(): void
    {
        $user = (new User())
            ->setEmail('john@test.com')
            ->setFirstname('Jean')
            ->setLastname('Dupont')
            ->setPassword('MotDePasse1!');

        $this->passwordHasher->method('hashPassword')->willReturn('hashed-password');
        $this->userRepository->expects($this->once())->method('save')->with($user, true);

        $this->userManager->createUser($user);

        $this->assertSame('hashed-password', $user->getPassword());
        $this->assertSame('jean-dupont', $user->getSlug());
    }
}
