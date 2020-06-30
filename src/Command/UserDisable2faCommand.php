<?php

namespace App\Command;

use App\Entity\User;
use App\Services\TFA\BackupCodeManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserDisable2faCommand extends Command
{
    protected static $defaultName = 'app:user-disable-2fa';

    protected $entityManager;
    protected $backupCodeManager;

    public function __construct(EntityManagerInterface $entityManager, BackupCodeManager $backupCodeManager)
    {
        parent::__construct(self::$defaultName);
        $this->entityManager = $entityManager;
        $this->backupCodeManager = $backupCodeManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Disable all Two-Factor Authentication methods for the given user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the new user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');

        $repo = $this->entityManager->getRepository(User::class);
        $user = $repo->findOneBy(['username' => $username]);

        if(!$user) {
            $io->error('User not found!');
            return self::FAILURE;
        }

        $io->warning('You are about to remove all Two-Factor-Authentication methods of following user: ' . $user->getUsername());
        $io->warning('Only continue if you are sure about the identity of the person that asked you to do this!');

        $continue = false;
        while (!$continue) {
            $continue = $io->confirm('Continue?', false);
        }

        //Disable google authenticator
        $user->setGoogleAuthenticatorSecret(null);
        //Disable backup codes
        $this->backupCodeManager->disableBackupCodesIfUnused($user);

        $this->entityManager->flush();

        $io->success('Two-Factor-Authentication disabled. The user should now be able to login again.');

        return 0;
    }
}
