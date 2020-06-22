<?php


namespace App\Controller;


use App\Entity\User;
use App\Form\User\PasswordChangeType;
use Doctrine\ORM\EntityManagerInterface;
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
    public function userSettings(Request $request, UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $entityManager): Response
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

        return $this->render('admin/user/settings.html.twig', [
            'pw_form' => $pw_form->createView()
        ]);
    }
}