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

        // Validate email
        if (!isset($data['email']) || empty($data['email'])) {
            return $this->json(['message' => 'Email is required'], 400);
        }

        if ($em->getRepository(User::class)->findOneBy(['email' => $data['email']])) {
            return $this->json(['message' => 'Email is already registered'], 400);
        }

        if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Invalid email format'], 400);
        }


        // Validate password
        if (!isset($data['password']) || empty($data['password'])) {
            return $this->json(['message' => 'Password is required'], 400);
        }

        if (strlen($data['password']) < 6 || strlen($data['password']) > 4096) {
            return $this->json(['message' => 'Password must be between 6 and 4096 characters long'], 400);
        }

        if ($data['password'] && preg_match('/\s/', $data['password'])) {
            return $this->json(['message' => 'Password cannot contain whitespace characters'], 400);
        }

        
      
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