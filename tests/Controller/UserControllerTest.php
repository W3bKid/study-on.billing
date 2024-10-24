<?php

namespace App\Tests\Controller;

use App\DataFixtures\UserFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = "/api/v1";
    protected $databaseTool;

    private $testEmail = "eamil@email.ru";
    private $testPassword = "password";
    private $token = "";

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

    public function testRegisterUser(): void
    {
        set_exception_handler(null);

        $this->client->jsonRequest(
            "POST",
            sprintf("%s%s", $this->path, "/register"),
            parameters: [
                "email" => $this->testEmail,
                "password" => $this->testPassword,
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertEquals(
            1,
            $this->repository->count(["email" => $this->testEmail])
        );

        $response = $this->client->getResponse()->getContent();
        $token = json_decode($response, true)["token"];

        $this->token = "Bearer . $token";
    }

    public function testAuthUser(): void
    {
        set_exception_handler(null);

        $this->client->jsonRequest(
            "POST",
            sprintf("%s%s", $this->path, "/register"),
            parameters: [
                "email" => $this->testEmail,
                "password" => $this->testPassword,
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertEquals(
            1,
            $this->repository->count(["email" => $this->testEmail])
        );

        $this->client->jsonRequest(
            "POST",
            sprintf("%s%s", $this->path, "/auth"),
            parameters: [
                "username" => $this->testEmail,
                "password" => $this->testPassword,
            ]
        );

        self::assertResponseIsSuccessful();

        $response = $this->client->getResponse()->getContent();
        self::assertArrayHasKey("token", json_decode($response, true));

        $token = json_decode($response, true)["token"];

        $this->token = "Bearer . $token";
    }

    public function testCurrentUser(): void
    {
        set_exception_handler(null);

        $this->client->jsonRequest(
            "POST",
            sprintf("%s%s", $this->path, "/register"),
            parameters: [
                "email" => $this->testEmail,
                "password" => $this->testPassword,
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertEquals(
            1,
            $this->repository->count(["email" => $this->testEmail])
        );

        $this->client->jsonRequest(
            "POST",
            sprintf("%s%s", $this->path, "/auth"),
            parameters: [
                "username" => $this->testEmail,
                "password" => $this->testPassword,
            ]
        );

        self::assertResponseIsSuccessful();

        $response = $this->client->getResponse()->getContent();
        $token = json_decode($response, true)["token"];

        $this->client->request(
            "GET",
            "/api/v1/users/current",
            [],
            [],
            ["HTTP_AUTHORIZATION" => "Bearer " . '312312312312']
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);


        $this->client->request(
            "GET",
            "/api/v1/users/current",
            [],
            [],
            ["HTTP_AUTHORIZATION" => "Bearer " . $token]
        );

        $response = $this->client->getResponse()->getContent();
        self::assertArrayHasKey("balance", json_decode($response, true));
        self::assertArrayHasKey("username", json_decode($response, true));
        self::assertArrayHasKey("roles", json_decode($response, true));
    }

    public function testRegisterUserWithoutPassword(): void
    {
        set_exception_handler(null);

        $this->client->jsonRequest(
            "POST",
            sprintf("%s%s", $this->path, "/register"),
            parameters: [
                "email" => $this->testEmail,
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterUserWithoutEmail(): void
    {
        set_exception_handler(null);

        $this->client->jsonRequest(
            "POST",
            sprintf("%s%s", $this->path, "/register"),
            parameters: [
                "email" => $this->testEmail,
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testAuthUserWithoutEmail(): void
    {
        set_exception_handler(null);

        $this->client->jsonRequest(
            "POST",
            sprintf("%s%s", $this->path, "/auth"),
            parameters: [
                "email" => $this->testEmail,
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCurrentUserWithoutToken()
    {
        set_exception_handler(null);

        $client = static::getClient();

        $client->request('GET', '/api/v1/users/current', [], [], ['HTTP_AUTHORIZATION' => 'Bearer' . " "]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAdmin() {
        set_exception_handler(null);

        $client = static::getClient();

        $email = "admin@billing.ru";
        $password = "12345678";

        $client->jsonRequest('POST', '/api/v1/auth', [
            "username" => $email,
            "password" => $password
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $token = $responseData['token'];
        $this->assertTrue(isset($token));
    }

    public function testAdminFailed() {
        set_exception_handler(null);

        $client = static::getClient();

        $email = "admin@billing.ru";
        $password = "pas";

        $client->jsonRequest('POST', '/api/v1/auth', [
            "username" => $email,
            "password" => $password
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $email = "email";
        $password = "12345678";

        $client->jsonRequest('POST', '/api/v1/auth', [
            "username" => $email,
            "password" => $password
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
