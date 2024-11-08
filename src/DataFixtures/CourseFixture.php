<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Enum\CourseType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixture extends Fixture
{
    private $courses = [
        'Основы программирования' => [
            'description' => 'Этот курс предназначен для начинающих, которые хотят освоить основы программирования.',
            'character_code' => 'osnovy_programmirovaniya',
            'price' => 100.00,
            'type' => CourseType::FULL_PAYMENT
        ],
        'Основы личной финансовой грамотности' => [
            'description' => 'Вы научитесь составлять бюджет, планировать сбережения и обязательно разберетесь в тонкостях кредитования.',
            'character_code' => 'osnovy_lichnoj_finansovoj_gramotnosti',
            'price' => 100.00,
            'type' => CourseType::RENTAL
        ],
        'Основы фотографии' => [
            'description' => 'Данный курс предлагает вводное обучение основам фотографии. Вы узнаете о композиции, освещении и методах постобработки, которые помогут улучшить качество ваших снимков.',
            'character_code' => 'osnovy_fotografii',
            'price' => 100.11,
            'type' => CourseType::FREE
        ]
    ];



    public function load(ObjectManager $manager): void
    {
        foreach($this->courses as $title => $courseData) {
            $course = new Course();
            $course->setTitle($title)
                ->setCharacterCode($courseData['character_code'])
                ->setPrice((float)$courseData['price'])
                ->setType($courseData['type']);

            $manager->persist($course);
        }
        $manager->flush();
    }
}
