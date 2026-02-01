<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login() {
        // This is intercepted by json_login in security.yaml
        // Controller code is never executed
        throw new \LogicException('Error logging in dude');
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher) {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']))
            return $this->json(['error' => 'Email is required']);

        if (!isset($data['password']))
            return $this->json(['error' => 'Password is required']);

        $user = (new User());
        $user->setEmail($data['email'])
            ->setPassword($passwordHasher->hashPassword($user, $data['password']))
        ;

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail()
            ]
        ], 201);
    }
}