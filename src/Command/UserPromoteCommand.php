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

class UserPromoteCommand extends Command
{
    protected static $defaultName = 'app:user-promote';

    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct(self::$defaultName);
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a role to the given user, to give him actions to certain operations.')
            ->addArgument('username', InputArgument::REQUIRED, 'The user you want to promote')
            ->addOption('role', null, InputOption::VALUE_OPTIONAL, 'The role this user should be given (e.g. ROLE_EDIT_USER)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');

        $repo = $this->entityManager->getRepository(User::class);
        $user = $repo->findOneBy(['username' => $username]);

        if($user) {
            $io->note(sprintf('You are about to change the following user: %s', $user->getUsername()));
        } else {
            $io->error('User not found!');
            return self::FAILURE;
        }

        $new_role = $input->getOption('role');
        if ($new_role === null) {
            while(empty($new_role)) {
                $new_role = $io->ask('Input the ROLE that should be added (e.g. ROLE_EDIT_USER)');
            }
        }

        $new_role = strtoupper($new_role);

        $user->addRole($new_role);

        //Save changes to DB
        $this->entityManager->flush();

        $io->success('User was promoted successfully.');

        return 0;
    }
}
