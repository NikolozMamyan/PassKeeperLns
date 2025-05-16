<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'heading'=> ''
        ]);
    }

        #[Route(path: '/security', name: 'app_security')]
    public function security(): Response
    {
        $user = $this->getUser();

        return $this->render('security/security.html.twig', [
            'user' => $user,
            'heading' => 'Security'
        ]);
    }

            #[Route(path: '/token/generate', name: 'app_token_generate')]
    public function tokenGenerate(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $user ->regenerateApiToken();

        $em->persist($user);
        $em->flush();

        return $this->render('security/security.html.twig', [
            'user' => $user,
            'heading' => 'Security'
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
