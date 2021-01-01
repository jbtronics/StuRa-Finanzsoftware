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

namespace App\Controller;

use App\Entity\User;
use App\Form\TFA\TFAGoogleSettingsType;
use App\Form\User\PasswordChangeType;
use App\Services\TFA\BackupCodeManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticator;
use Scheb\TwoFactorBundle\Security\TwoFactor\QrCode\QrCodeGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/admin/user")
 */
class UserSettingsController extends AbstractController
{
    /**
     * @Route("/settings", name="user_settings")
     */
    public function userSettings(Request $request, UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $entityManager,
        GoogleAuthenticator $googleAuthenticator, QrCodeGenerator $qrCodeGenerator, BackupCodeManager $backupCodeManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new RuntimeException('This controller can only manage App\Entity\User objects!');
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
        if (!$google_enabled && !$google_form->isSubmitted()) {
            $user->setGoogleAuthenticatorSecret($googleAuthenticator->generateSecret());
            $google_form->get('googleAuthenticatorSecret')
                ->setData($user->getGoogleAuthenticatorSecret());
        }
        $google_form->handleRequest($request);

        if ($google_form->isSubmitted() && $google_form->isValid()) {
            if (!$google_enabled) {
                //Save 2FA settings (save secrets)
                $user->setGoogleAuthenticatorSecret($google_form->get('googleAuthenticatorSecret')->getData());
                $backupCodeManager->enableBackupCodes($user);

                $entityManager->flush();
                $this->addFlash('success', 'user.settings.2fa.google.activated');

                return $this->redirect($request->getUri());
            }

            //Remove secret to disable google authenticator
            $user->setGoogleAuthenticatorSecret(null);
            $backupCodeManager->disableBackupCodesIfUnused($user);
            $entityManager->flush();
            $this->addFlash('success', 'user.settings.2fa.google.disabled');

            return $this->redirect($request->getUri());
        }

        return $this->render('admin/user/settings.html.twig', [
            'user' => $user,
            'pw_form' => $pw_form->createView(),
            'google_form' => $google_form->createView(),
            'tfa_google' => [
                'enabled' => $google_enabled,
                'qrContent' => $googleAuthenticator->getQRContent($user),
                'secret' => $user->getGoogleAuthenticatorSecret(),
                'qrImageDataUri' => $qrCodeGenerator->getGoogleAuthenticatorQrCode($user)
                    ->writeDataUri(),
                'username' => $user->getGoogleAuthenticatorUsername(),
            ],
        ]);
    }

    /**
     * @Route("/2fa_backup_codes", name="show_backup_codes")
     */
    public function showBackupCodes(): Response
    {
        $user = $this->getUser();

        //When user change its settings, he should be logged  in fully.
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$user instanceof User) {
            throw new RuntimeException('This controller only works only for Part-DB User objects!');
        }

        if (empty($user->getBackupCodes())) {
            $this->addFlash('error', 'tfa_backup.no_codes_enabled');

            throw new RuntimeException('You do not have any backup codes enabled, therefore you can not view them!');
        }

        return $this->render('admin/user/backup_codes.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/regenerate_backup_codes", name="tfa_regenerate_backup_codes", methods={"DELETE"})
     */
    public function regenerateBackupCodes(Request $request, EntityManagerInterface $entityManager, BackupCodeManager $backupCodeManager): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $user = $this->getUser();

        //When user change its settings, he should be logged  in fully.
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$user instanceof User) {
            throw new RuntimeException('This controller only works only for Part-DB User objects!');
        }

        if ($this->isCsrfTokenValid('regenerate_backup_codes'.$user->getId(), $request->request->get('_token'))) {
            $backupCodeManager->regenerateBackupCodes($user);
            $entityManager->flush();
            $this->addFlash('success', 'user.settings.2fa.backup_codes.regenerated');
        } else {
            $this->addFlash('error', 'csfr_invalid');
        }

        return $this->redirect($request->request->get('_redirect'));
    }

    /**
     * @Route("/invalidate_trustedDevices", name="tfa_trustedDevices_invalidate", methods={"DELETE"})
     *
     * RedirectResponse
     */
    public function resetTrustedDevices(Request $request, EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $user = $this->getUser();

        //When user change its settings, he should be logged  in fully.
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$user instanceof User) {
            throw new RuntimeException('This controller only works only for Part-DB User objects!');
        }

        if ($this->isCsrfTokenValid('devices_reset'.$user->getId(), $request->request->get('_token'))) {
            $user->invalidateTrustedDevices();
            $entityManager->flush();
            $this->addFlash('success', 'tfa_trustedDevice.invalidate.success');
        } else {
            $this->addFlash('error', 'csfr_invalid');
        }

        return $this->redirect($request->request->get('_redirect'));
    }
}
