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
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserChangePasswordCommand extends Command
{
    protected static $defaultName = 'app:user-change-password';

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
        /** @var User $user */
        $user = $repo->findOneBy(['username' => $username]);

        if ($user === null) {
            $io->error('No user found with username ' . $username);
            return self::FAILURE;
        }

        $io->confirm('You are about to change the password of following user: ' . $username . ' Continue?');

        $password = $input->getOption('password');

        while (empty($password)) {
            $password = $io->askHidden('Please enter a new password for the user! (Input is not shown)');
            if (empty($password)) {
                $io->warning('Password must not be empty!');
            }
        }

        $encoded = $this->passwordEncoder->encodePassword($user, $password);
        $user->setPassword($encoded);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Password was changed successfully');

        return 0;
    }
}