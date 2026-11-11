<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TermsController extends AbstractController
{
    // Change this whenever the terms text changes. Each application saves the version it agreed to.
    public const VERSION = '2026-11-07';

    #[Route('/terms', name: 'app_terms', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('terms/index.html.twig');
    }
}
