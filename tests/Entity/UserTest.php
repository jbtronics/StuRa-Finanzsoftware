<?php
/*
 * Copyright (C) 2020  Jan Böhmer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetRoles(): void
    {
        $user = new User();
        //Ensure that a user always has ROLE_USER
        $user->setRoles([]);
        static::assertContains('ROLE_USER', $user->getRoles());
        //Ensure that all roles are unique
        $user->setRoles(['ROLE_TEST', 'ROLE_TEST']);
        static::assertSame(array_unique($user->getRoles()), $user->getRoles());
    }

    public function testAddRole(): void
    {
        $user = new User();
        $user->setRoles([]);
        //Ensure that roles are empty
        static::assertSame(['ROLE_USER'], $user->getRoles());

        //Add a new role and ensure it was added
        $user->addRole('ROLE_TEST');
        static::assertContains('ROLE_TEST', $user->getRoles());
    }

    public function testEraseCredentials(): void
    {
        //Ensure that test erase credentials remove the plaintext password
        $user = new User();
        $user->setPlainPassword('secret_password');
        static::assertSame('secret_password', $user->getPlainPassword());
        $user->eraseCredentials();
        static::assertNull($user->getPlainPassword());
    }

    /**
     * @dataProvider fullNameDataProvider
     */
    public function testGetFullName(string $expected, string $first_name, string $last_name): void
    {
        $user = new User();
        $user->setFirstName($first_name)
            ->setLastName($last_name);
        static::assertSame($expected, $user->getFullName());
    }

    public function fullNameDataProvider(): \Iterator
    {
        yield ['John Doe', 'John', 'Doe'];
        yield ['Admin', 'Admin', ''];
        yield ['Admin', '', 'Admin'];
        yield ['John Jane Doe', 'John Jane', 'Doe'];
    }

    public function testGoogleAuthenticatorUsername(): void
    {
        $user = new User();
        $user->setUsername('test_user');
        static::assertSame('test_user', $user->getGoogleAuthenticatorUsername());
    }

    public function testIsGoogleAuthenticatorEnabled(): void
    {
        $user = new User();
        $user->setGoogleAuthenticatorSecret('google_secret');
        static::assertTrue($user->isGoogleAuthenticatorEnabled());
        $user->setGoogleAuthenticatorSecret(null);
        static::assertFalse($user->isGoogleAuthenticatorEnabled());
        $user->setGoogleAuthenticatorSecret('');
        static::assertFalse($user->isGoogleAuthenticatorEnabled());
    }

    public function testIsTFAEnabled(): void
    {
        $user = new User();
        $user->setGoogleAuthenticatorSecret('google_secret');
        static::assertTrue($user->isTFAEnabled());
        $user->setGoogleAuthenticatorSecret(null);
        static::assertFalse($user->isTFAEnabled());
        $user->setGoogleAuthenticatorSecret('');
        static::assertFalse($user->isTFAEnabled());
    }

    public function testInvalidateTrustedDevices(): void
    {
        //Trusted devices are invalidated when the new token version is greater than before
        $user = new User();
        $tmp = $user->getTrustedTokenVersion();
        $user->invalidateTrustedDevices();
        static::assertGreaterThan($tmp, $user->getTrustedTokenVersion());
    }

    public function testIsBackupCode(): void
    {
        $user = new User();
        $user->setBackupCodes([]);
        //Nothing can be a backup code if no codes are defined.
        static::assertFalse($user->isBackupCode('code1'));

        //Test if backup codes are set
        $user->setBackupCodes(['code1', 'code2', 'code3', 'code4']);
        static::assertTrue($user->isBackupCode('code1'));
        static::assertFalse($user->isBackupCode('other_code'));
        //Backup codes are case sensitive
        static::assertFalse($user->isBackupCode('Code1'));
        //Backup codes must not be removed when they are checked
        static::assertTrue($user->isBackupCode('code1'));
    }

    public function testInvalidateBackupCode(): void
    {
        $user = new User();
        //Test if backup codes are set
        $user->setBackupCodes(['code1', 'code2', 'code3', 'code4']);

        //Test if we can invalidate a single code
        $user->invalidateBackupCode('code1');
        static::assertFalse($user->isBackupCode('code1'));

        //If we invalidate a non existing code nothing must happen
        $user->invalidateBackupCode('Code2');
        $user->invalidateBackupCode('invalid');
        self::assertEqualsCanonicalizing(['code2', 'code3', 'code4'], $user->getBackupCodes());
    }

    public function testGetBackupCodesDate(): void
    {
        $user = new User();
        //For a new user the date must be null
        static::assertNull($user->getBackupCodesDate());
        //After we set backup codes, the value must be update
        $user->setBackupCodes(['code1']);
        static::assertNotNull($user->getBackupCodesDate());
    }

    /**
     * @dataProvider toStringDataProvider
     */
    public function testToString(string $expected, string $first_name, string $last_name, string $username): void
    {
        $user = new User();
        $user->setUsername($username);
        $user->setFirstName($first_name)
            ->setLastName($last_name);
        static::assertSame($expected, (string) $user);
    }

    public function toStringDataProvider(): \Iterator
    {
        yield ['John Doe (test)', 'John', 'Doe', 'test'];
        yield ['John (test)', 'John', '', 'test'];
        yield ['John (test)', '', 'John', 'test'];
    }
}
