<?php

namespace App\Tests;

use App\DataFixtures\TransactionFixture;
use App\DataFixtures\UserFixtures;
use App\Entity\Transaction;
use App\Entity\User;
use App\Tests\Utils\AuthUser;
use App\Tests\Utils\JWTParse;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use phpDocumentor\Reflection\Types\Self_;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PaymentTest extends WebTestCase
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

        $this->databaseTool->loadFixtures([TransactionFixture::class]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    public function testBuyPaidCourse(): void
    {
        set_exception_handler(null);
        $token = (new AuthUser())->login($this->client, 'test@email.com', '12345678')['token'];
        $username = JWTParse::parse($token)['username'];
        $userBalanceBeforeBuy = $this->manager->getRepository(User::class)->findOneBy(['email' => $username])->getBalance();
        $transactionCount = $this->manager->getRepository(Transaction::class)->count([]);
        $this->client->jsonRequest(
            'POST',
            sprintf("%s%s%s%s", $this->path, "/courses/", 'buy_course/', 'pay'),
        );
        $userBalanceAfterBuy = $this->manager->getRepository(User::class)->findOneBy(['email' => $username])->getBalance();
        self::assertEquals($userBalanceBeforeBuy, $userBalanceAfterBuy);
        self::assertEquals($transactionCount, $this->manager->getRepository(Transaction::class)->count([]));
        self::assertResponseIsSuccessful();
    }

    public function testPaySuccess(): void
    {
        set_exception_handler(null);
        $token = (new AuthUser())->login($this->client, 'test@email.com', '12345678')['token'];
        $username = JWTParse::parse($token)['username'];
        $userBalanceBeforeBuy = $this->manager->getRepository(User::class)->findOneBy(['email' => $username])->getBalance();
        $transactionCount = $this->manager->getRepository(Transaction::class)->count([]);
        $this->client->jsonRequest(
            'POST',
            sprintf("%s%s%s%s", $this->path, "/courses/", 'courseThatYouMusBuy/', 'pay'),
        );
        $userBalanceAfterBuy = $this->manager->getRepository(User::class)->findOneBy(['email' => $username])->getBalance();
        self::assertNotEquals($userBalanceBeforeBuy, $userBalanceAfterBuy);
        self::assertEquals($transactionCount + 1, $this->manager->getRepository(Transaction::class)->count([]));
        self::assertResponseIsSuccessful();
    }

    public function testNoMoney(): void
    {
        set_exception_handler(null);
        $token = (new AuthUser())->login($this->client, 'test@email.com', '12345678')['token'];
        $username = JWTParse::parse($token)['username'];
        $userBalanceBeforeBuy = $this->manager->getRepository(User::class)->findOneBy(['email' => $username])->getBalance();
        $transactionCount = $this->manager->getRepository(Transaction::class)->count([]);
        $this->client->jsonRequest(
            'POST',
            sprintf("%s%s%s%s", $this->path, "/courses/", 'rich_course/', 'pay'),
        );
        $userBalanceAfterBuy = $this->manager->getRepository(User::class)->findOneBy(['email' => $username])->getBalance();
        self::assertEquals($userBalanceBeforeBuy, $userBalanceAfterBuy);
        self::assertEquals($transactionCount , $this->manager->getRepository(Transaction::class)->count([]));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_ACCEPTABLE);
    }

    public function testPayExpiredCourse(): void
    {
        set_exception_handler(null);
        $token = (new AuthUser())->login($this->client, 'test@email.com', '12345678')['token'];
        $username = JWTParse::parse($token)['username'];
        $userBalanceBeforeBuy = $this->manager->getRepository(User::class)->findOneBy(['email' => $username])->getBalance();
        $transactionCount = $this->manager->getRepository(Transaction::class)->count();
        $this->client->jsonRequest(
            'POST',
            sprintf("%s%s%s%s", $this->path, "/courses/", 'rental_course_expired/', 'pay'),
        );
        $userBalanceAfterBuy = $this->manager->getRepository(User::class)->findOneBy(['email' => $username])->getBalance();
        self::assertNotEquals($userBalanceBeforeBuy, $userBalanceAfterBuy);
        self::assertEquals($transactionCount + 1, $this->manager->getRepository(Transaction::class)->count([]));
        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
