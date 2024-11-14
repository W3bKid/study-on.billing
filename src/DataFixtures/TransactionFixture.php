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
            ->setPassword($this->passwordHasher->hashPassword($user, "12345678"))
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


        $courseThatYouMusBuy = (new Course())->setType(CourseType::FULL_PAYMENT)
            ->setCharacterCode('courseThatYouMusBuy')
            ->setTitle('Курс покупки')
            ->setPrice(40.00);


        $manager->persist($buyCourse);
        $manager->persist($courseThatYouMusBuy);

        $richCourse = (new Course())->setType(CourseType::FULL_PAYMENT)
            ->setCharacterCode('rich_course')
            ->setTitle('Курс на который не хватит денег')
            ->setPrice(400000.00);

        $manager->persist($richCourse);

        $freeCourse = (new Course())->setType(CourseType::FREE)
            ->setCharacterCode('free_course')
            ->setTitle('free course title');

        $manager->persist($freeCourse);
        $manager->flush();

        $transactionForRent = (new Transaction())->setType(TransactionType::Payment)
            ->setValue(100.00)
            ->setBillingUser($user)
            ->setDate(new \DateTime())
            ->setCourse($rentCourse)
            ->setExpiredAt((new \DateTime('now'))->modify('+7 day'));

        $rentExpiredCourse = (new Course())->setType(CourseType::RENTAL)
            ->setCharacterCode('rental_course_expired')
            ->setTitle('rental_course_expired')
            ->setPrice(10);

        $manager->persist($rentExpiredCourse);
        $manager->persist($transactionForRent);

        $manager->flush();

        $expiredTransaction = (new Transaction())->setType(TransactionType::Payment)
            ->setValue($rentExpiredCourse->getPrice())
            ->setCourse($rentExpiredCourse)
            ->setDate((new \DateTime('now'))->modify('-10 day'))
            ->setExpiredAt((new \DateTime('now'))->modify('-10 day'))
            ->setBillingUser($user);

        $transaction = (new Transaction())->setType(TransactionType::Payment)
            ->setValue(100.00)
            ->setBillingUser($user)
            ->setDate(new \DateTime())
            ->setCourse($buyCourse)
            ->setExpiredAt((new \DateTime('now'))->modify('+7 day'));

        $manager->persist($expiredTransaction);
        $manager->persist($transaction);
        $manager->flush();
    }
}
