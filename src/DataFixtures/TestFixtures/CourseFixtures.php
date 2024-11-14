<?php

namespace App\DataFixtures\TestFixtures;

use App\Entity\Course;
use App\Entity\User;
use App\Enum\CourseType;
use App\Service\PaymentService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $coursePay = new Course();
        $courseFree = new Course();
        $courseRental = new Course();
        $coursePay->setType(CourseType::RENTAL)
            ->setCharacterCode('pay_course')
            ->setTitle('Pay Course')
            ->setPrice(10);
        $courseFree->setType(CourseType::FREE)
            ->setCharacterCode('new_course_free')
            ->setTitle('Free Course')
            ->setPrice(10);
        $courseRental->setType(CourseType::RENTAL)
            ->setCharacterCode('new_course_rental')
            ->setTitle('Rental Course')
            ->setPrice(10);

        $manager->persist($coursePay);
        $manager->persist($courseFree);
        $manager->persist($courseRental);
        $manager->flush();
    }
}
