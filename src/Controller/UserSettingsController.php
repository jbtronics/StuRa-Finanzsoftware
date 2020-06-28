<?php


namespace App\Controller;


use App\Entity\User;
use App\Form\TFA\TFAGoogleSettingsType;
use App\Form\User\PasswordChangeType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\QrCode\QrCodeGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserSettingsController extends AbstractController
{
    /**
     * @Route("/admin/user/settings", name="user_settings")
     * @return Response
     */
    public function userSettings(Request $request, UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $entityManager,
        GoogleAuthenticator $googleAuthenticator, QrCodeGenerator $qrCodeGenerator): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('This controller can only manage App\Entity\User objects!');
        }

        $pw_form = $this->createForm(PasswordChangeType::class);
        $pw_form->handleRequest($request);

        if ($pw_form->isSubmitted() && $pw_form->isValid()) {
            //If form is valid, the old password was already validated, so we just have to encrypt the pw now
            $hashed_pw = $passwordEncoder->encodePassword($user, $pw_form['plain_password']->getData());
            $user->setPassword($hashed_pw);

            $entityManager->flush();

            $this->addFlash('success', 'password.changed_successful');
        }

        //Handle 2FA things
        $google_form = $this->createForm(TFAGoogleSettingsType::class, $user);
        $google_enabled = $user->isGoogleAuthenticatorEnabled();
        if (! $google_enabled && ! $google_form->isSubmitted()) {
            $user->setGoogleAuthenticatorSecret($googleAuthenticator->generateSecret());
            $google_form->get('googleAuthenticatorSecret')->setData($user->getGoogleAuthenticatorSecret());
        }
        $google_form->handleRequest($request);

        if ($google_form->isSubmitted() && $google_form->isValid()) {
            if (! $google_enabled) {
                //Save 2FA settings (save secrets)
                $user->setGoogleAuthenticatorSecret($google_form->get('googleAuthenticatorSecret')->getData());
                //$backupCodeManager->enableBackupCodes($user);

                $entityManager->flush();
                $this->addFlash('success', 'user.settings.2fa.google.activated');

                return $this->redirect($request->getUri());;
            }

            //Remove secret to disable google authenticator
            $user->setGoogleAuthenticatorSecret(null);
            //$backupCodeManager->disableBackupCodesIfUnused($user);
            $entityManager->flush();
            $this->addFlash('success', 'user.settings.2fa.google.disabled');

            return $this->redirect($request->getUri());
        }

        return $this->render('admin/user/settings.html.twig', [
            'pw_form' => $pw_form->createView(),
            'google_form' => $google_form->createView(),
            'tfa_google' => [
                'enabled' => $google_enabled,
                'qrContent' => $googleAuthenticator->getQRContent($user),
                'secret' => $user->getGoogleAuthenticatorSecret(),
                'qrImageDataUri' => $qrCodeGenerator->getGoogleAuthenticatorQrCode($user)->writeDataUri(),
                'username' => $user->getGoogleAuthenticatorUsername(),
            ],
        ]);
    }
}