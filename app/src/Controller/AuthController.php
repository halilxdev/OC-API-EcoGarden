<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\MonthRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;


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
    #[Route('/api/user', name: 'createUser', methods: ['POST'])]
    public function createUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $em): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), type: User::class, format: 'json');
        $em->persist($user);
        $em->flush();
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getAuthors']);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }
}