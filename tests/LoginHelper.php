<?php

declare(strict_types=1);


namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class LoginHelper
{
    public static function loginAs(KernelBrowser $client, string $username): void
    {
        $client->loginUser($client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['username' => $username]));
    }

    public static function loginAsAdmin(KernelBrowser $client): void
    {
        self::loginAs($client, 'admin');
    }
}