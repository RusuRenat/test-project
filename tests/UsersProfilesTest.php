<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UsersProfilesTest extends WebTestCase
{
    public function testSomething(): void
    {

        $client = static::createClient();

        $headers = [
            'HTTP_AUTHORIZATION' => "Bearer {$_ENV['BEARER_TOKEN']}",
            'CONTENT_TYPE' => 'application/json',
        ];

        $crawler = $client->request('GET', $_ENV['API_URL'] . '/api/v1/secured/users-profiles', [], [], $headers);

        $this->assertResponseIsSuccessful();
//        $this->assertSelectorTextContains('h1', 'Hello World');
    }
}
