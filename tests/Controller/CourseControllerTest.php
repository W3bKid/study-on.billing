<?php

namespace App\Tests\Controller;

use App\DataFixtures\TestFixtures\CourseFixtures;
use App\DataFixtures\TransactionFixture;
use App\DataFixtures\UserFixtures;
use App\Entity\Course;
use App\Entity\User;
use App\Tests\Utils\AuthUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CourseControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = "/api/v1";
    protected $databaseTool;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get("doctrine")->getManager();
        $this->repository = $this->manager->getRepository(User::class);

        $this->databaseTool = static::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get();

        $this->databaseTool->loadFixtures([UserFixtures::class]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }


    public function testList(): void
    {
        set_exception_handler(null);

        $this->databaseTool->loadFixtures([CourseFixtures::class]);

        $this->client->jsonRequest(
            "GET",
            sprintf("%s%s", $this->path, "/courses/"));


        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame("content-type", "application/json");

        $response = json_decode($this->client->getResponse()->getContent(), true);

        foreach ($response as $course) {
            self::assertNotEmpty($course["character_code"]);
            self::assertNotEmpty($course["type"]);

            if ($course['type'] !== 'Free') {
                self::assertNotEmpty($course["price"]);
            }
        }
    }

    public function testCourseByCode(): void
    {
        set_exception_handler(null);

        $this->databaseTool->loadFixtures([CourseFixtures::class]);

        $this->client->jsonRequest(
            "GET",
            sprintf("%s%s", $this->path, "/courses/pay_course"));

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame("content-type", "application/json");

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertNotEmpty($response["code"]);
        self::assertNotNull($response["type"]);
        self::assertNotNull($response["price"]);

        $this->client->jsonRequest(
            "GET",
            sprintf("%s%s", $this->path, "/courses/beleberda"));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateFreeCourse(): void {
        set_exception_handler(null);
        $this->databaseTool->loadFixtures([UserFixtures::class]);
        (new AuthUser())->login($this->client, 'admin@billing.ru', '12345678');
        $this->client->jsonRequest(
            'POST',
            sprintf("%s%s", $this->path, "/courses/"),
            parameters: [
                'type' => "Free",
                'title' => "Free course",
                'character_code' => "course_Free",
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertEquals(1, $this->manager->getRepository(Course::class)->count());

        $this->client->jsonRequest(
            'POST',
            sprintf("%s%s", $this->path, "/courses/"),
            parameters: [
                'price' => 13.00,
                'type' => "Full Payment",
                'title' => "Free course",
                'character_code' => "course_Full",
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertEquals(2, $this->manager->getRepository(Course::class)->count());

        $this->client->jsonRequest(
            'POST',
            sprintf("%s%s", $this->path, "/courses/"),
            parameters: [
                'price' => 13.00,
                'type' => "Rental",
                'title' => "Rental course",
                'character_code' => "course_Rental",
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertEquals(3, $this->manager->getRepository(Course::class)->count());

        $this->client->jsonRequest(
            'POST',
            sprintf("%s%s", $this->path, "/courses/"),
            parameters: [
                'type' => "Rental",
                'title' => "Rental course",
                'character_code' => "course_Rental",
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertEquals(3, $this->manager->getRepository(Course::class)->count());

        $this->client->jsonRequest(
            'POST',
            sprintf("%s%s", $this->path, "/courses/"),
            parameters: [
                'type' => "Payment",
                'title' => "Rental course",
                'character_code' => "course_Rental",
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertEquals(3, $this->manager->getRepository(Course::class)->count());

        $this->client->jsonRequest(
            'POST',
            sprintf("%s%s", $this->path, "/courses/"),
            parameters: [
                'price' => 13.00,
                'type' => "Payment",
                'title' => "Rental course",
                'character_code' => "course_Rental",
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertEquals(3, $this->manager->getRepository(Course::class)->count());
    }

    public function testEditCourse(): void
    {
        set_exception_handler(null);
        $this->databaseTool->loadFixtures([CourseFixtures::class, UserFixtures::class]);
        (new AuthUser())->login($this->client, 'admin@billing.ru', '12345678');
        $course = $this->manager->getRepository(Course::class)->find(1);

        $params = [
            'type' => "Free",
            'title' => "Free course",
            'character_code' => "course_Free",
        ];

        $this->client->jsonRequest(
            'PUT',
            sprintf("%s%s%s", $this->path, "/courses/", $course->getCharacterCode()),
            parameters: $params
        );

        $course = $this->manager->getRepository(Course::class)->find(1);
        self::assertResponseStatusCodeSame(200);
        self::assertEquals($params["title"], $course->getTitle());
        self::assertEquals(3, $course->getType());
        self::assertEquals($params["character_code"], $course->getCharacterCode());

        $this->client->jsonRequest(
            'PUT',
            sprintf("%s%s%s", $this->path, "/courses/", 'adbrakadabra'),
            parameters: $params
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->client->jsonRequest(
            'PUT',
            sprintf("%s%s%s", $this->path, "/courses/", 'course_Free'),
            parameters: [
                'type' => "Rental",
                'title' => "Rental course",
                'character_code' => "course_Rental",
            ]
        );
        $responseBody = json_decode($this->client->getResponse()->getContent());

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertEquals($responseBody->message, 'Course with Payment or Rental type must have price');
        self::assertEquals($responseBody->code, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCourseIsPaid(): void
    {
        set_exception_handler(null);
        $this->databaseTool->loadFixtures([UserFixtures::class, TransactionFixture::class]);
        (new AuthUser())->login($this->client, 'test@email.com', '12345678');

        $this->client->jsonRequest(
            'GET',
            sprintf("%s%s%s%s", $this->path, "/courses/", 'free_course/', 'is-paid'),
        );

        $message = json_decode($this->client->getResponse()->getContent())->message;
        self::assertTrue($message);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->client->jsonRequest(
            'GET',
            sprintf("%s%s%s%s", $this->path, "/courses/", 'rich_course/', 'is-paid'),
        );

        $message = json_decode($this->client->getResponse()->getContent())->message;
        self::assertFalse($message);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->client->jsonRequest(
            'GET',
            sprintf("%s%s%s%s", $this->path, "/courses/", 'buy_course/', 'is-paid'),
        );

        $message = json_decode($this->client->getResponse()->getContent())->message;
        self::assertTrue($message);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->client->jsonRequest(
            'GET',
            sprintf("%s%s%s%s", $this->path, "/courses/", 'rental_course_expired/', 'is-paid'),
        );

        $message = json_decode($this->client->getResponse()->getContent())->message;
        self::assertFalse($message);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}