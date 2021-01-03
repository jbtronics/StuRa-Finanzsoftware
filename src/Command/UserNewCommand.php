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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserNewCommand extends Command
{
    protected static $defaultName = 'app:user-new';

    protected $entityManager;
    protected $passwordEncoder;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct(static::$defaultName);
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function configure()
    {
        $this
            ->setDescription('Create a new user. Useful if no user is existing yet...')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the new user.')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'The password of the new user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');

        $io->confirm('You are about to create a new user with username: '.$username.' Continue?');

        $user = new User();
        $user->setUsername($username);

        $password = $input->getOption('password');

        while (empty($password)) {
            $password = $io->askHidden('Please enter a new password for the user! (Input is not shown)');
            if (empty($password)) {
                $io->warning('Password must not be empty!');
            }
        }

        $encoded = $this->passwordEncoder->encodePassword($user, $password);
        $user->setPassword($encoded);

        //Give user all roles
        $user->setRoles([
                            'ROLE_ADMIN',
                            'ROLE_EDIT_USER',
                            'ROLE_EDIT_ORGANISATIONS',
                            'ROLE_SHOW_PAYMENT_ORDERS',
                            'ROLE_EDIT_PAYMENT_ORDERS',
                            'ROLE_PO_FACTUALLY',
                            'ROLE_PO_MATHEMATICALLY',
                        ]);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('User was created successfully.');

        return 0;
    }
}
