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

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

final class UserChangePasswordCommand extends Command
{
    protected static $defaultName = 'app:user-change-password';

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UserPasswordHasherInterface $passwordEncoder)
    {
        parent::__construct(static::$defaultName);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Change password of the given user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the new user.')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'The password of the new user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');

        $repo = $this->entityManager->getRepository(User::class);
        /** @var User|null $user */
        $user = $repo->findOneBy([
            'username' => $username,
        ]);

        if (null === $user) {
            $io->error('No user found with username '.$username);

            return self::FAILURE;
        }

        $io->confirm('You are about to change the password of following user: '.$username.' Continue?');

        $password = $input->getOption('password');

        while (empty($password)) {
            $password = $io->askHidden('Please enter a new password for the user! (Input is not shown)');
            if (empty($password)) {
                $io->warning('Password must not be empty!');
            }
        }

        $encoded = $this->passwordEncoder->hashPassword($user, $password);
        $user->setPassword($encoded);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Password was changed successfully');

        return 0;
    }
}
