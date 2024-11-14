<?php

namespace App\Tests\Utils;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\HttpFoundation\Response;

class AuthUser {
    public function login(AbstractBrowser $client, string $email, string $password): array
    {
        $client->jsonRequest('POST', '/api/v1/auth', [
            "username" => $email,
            "password" => $password
        ]);

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $responseData['token']);

        return $responseData;
    }

    public function logout($client): void
    {
        $client->setServerParameter('HTTP_AUTHORIZATION', '');
    }
}