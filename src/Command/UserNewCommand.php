<?php

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

        $io->confirm('You are about to create a new user with username: ' . $username . ' Continue?');

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
