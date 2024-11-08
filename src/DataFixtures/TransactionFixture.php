<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\CourseType;
use App\Enum\TransactionType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TransactionFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = (new User());
        $user->setEmail('test@email.com')
            ->setRoles(['ROLE_USER'])
            ->setPassword($this->passwordHasher->hashPassword($user, "test"))
            ->setBalance(100.00);

        $manager->persist($user);

        $rentCourse = (new Course())->setType(CourseType::RENTAL)
            ->setCharacterCode('rental_course')
            ->setPrice(20.00)
            ->setTitle('Курс аренды');

        $manager->persist($rentCourse);
        $manager->flush();

        $buyCourse = (new Course())->setType(CourseType::FULL_PAYMENT)
            ->setCharacterCode('buy_course')
            ->setTitle('Курс покупки')
            ->setPrice(40.00);

        $manager->persist($buyCourse);

        $courseForBuying = (new Course())->setType(CourseType::FULL_PAYMENT)
            ->setCharacterCode('buy_course')
            ->setTitle('Курс для покупки')
            ->setPrice(10.00);

        $manager->persist($buyCourse);


        $richCourse = (new Course())->setType(CourseType::FULL_PAYMENT)
            ->setCharacterCode('rich_course')
            ->setTitle('Курс на который не хватит денег')
            ->setPrice(40.00);

        $manager->persist($richCourse);
        $manager->flush();

        $transactionForRent = (new Transaction())->setType(TransactionType::Payment)
            ->setValue(100.00)
            ->setBillingUser($user)
            ->setDate(new \DateTime())
            ->setCourse($rentCourse)
            ->setExpiredAt((new \DateTime('now'))->modify('+7 day'));

        $manager->persist($transactionForRent);

        $transaction = (new Transaction())->setType(TransactionType::Payment)
            ->setValue(100.00)
            ->setBillingUser($user)
            ->setDate(new \DateTime())
            ->setCourse($buyCourse)
            ->setExpiredAt((new \DateTime('now'))->modify('+7 day'));

        $manager->persist($transaction);

        $manager->flush();
    }
}
