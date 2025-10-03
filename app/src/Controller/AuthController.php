<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    /**
     * Cette méthode permet de créer un nouvel utilisateur.
     * Exemple de données :
     * {
     *      "username": "votre@adresse.mail",
     *      "password": "m0t_dePasse",
     *      "zip_code": 12345
     * }
     *
     * @return JsonResponse
     */
    #[Route('/api/user', name: 'user_new', methods: ['POST'])]
    public function createUser(): JsonResponse
    {
        
    }
}
