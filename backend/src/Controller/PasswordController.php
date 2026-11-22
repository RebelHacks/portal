<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\NewPasswordType;
use App\Security\LoginLinkSuccessHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class PasswordController extends AbstractController
{
    // The page after logging in with an emailed link: set a new password without the old one.
    // Only that login opens it, and only for a few minutes (see LoginLinkSuccessHandler).
    #[Route('/password/new', name: 'app_password_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        Security $security,
    ): Response {
        $session = $request->getSession();

        if ($session->get(LoginLinkSuccessHandler::MAY_SET_PASSWORD_UNTIL, 0) < time()) {
            $session->remove(LoginLinkSuccessHandler::MAY_SET_PASSWORD_UNTIL);

            return $this->redirectToRoute('app_lobby');
        }

        // Visitors without a session never get here: access_control sends them to log in.
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createFormBuilder()
            ->add('plainPassword', NewPasswordType::class, [
                'first_options' => ['label' => 'New password', 'attr' => ['autocomplete' => 'new-password', 'autofocus' => true]],
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $em->flush();
            $session->remove(LoginLinkSuccessHandler::MAY_SET_PASSWORD_UNTIL);

            // A changed password ends every session of this account, this one included. Log back in here.
            $security->login($user, 'form_login', 'main');
            $this->addFlash('success', 'Your password is changed.');

            return $this->redirectToRoute('app_lobby');
        }

        return $this->render('security/new_password.html.twig', ['form' => $form]);
    }
}
