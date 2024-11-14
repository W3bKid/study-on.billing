<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\PaymentService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;
    private PaymentService $paymentService;

    public function __construct(UserPasswordHasherInterface $passwordHasher, PaymentService $paymentService)
    {
        $this->passwordHasher = $passwordHasher;
        $this->paymentService = $paymentService;
    }

    public function load(ObjectManager $manager): void
    {
        $adminUser = new User();
        $adminUser->setEmail("admin@billing.ru");
        $adminUser->setRoles(["ROLE_SUPER_ADMIN"]);
        $adminUser->setPassword(
            $this->passwordHasher->hashPassword($adminUser, "12345678")
        );
        $manager->persist($adminUser);

        $user = new User();
        $user->setEmail("user@billing.ru");
        $user->setRoles(["ROLE_USER"]);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, "12345678")
        );
        $manager->persist($user);
        $manager->flush();
        $this->paymentService->deposit($adminUser, 100.00);
        $this->paymentService->deposit($user, 100.00);
    }
}
