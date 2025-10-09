<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Entity\Month;
use App\Repository\AdviceRepository;
use App\Repository\MonthRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AdviceController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer tous les conseils d'un mois précis ou de celui en cours
     *
     * @param AdviceRepository
     * @param SerializerInterface
     * @return JsonResponse
     */
    #[Route('/api/conseil', name: 'advice_current_month', methods: ['GET'])]
    #[Route('/api/conseil/{id}', name: 'advice', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER', message: 'Vous devez vous authentifier.')]
    public function getAllAdvicesThisMonth(AdviceRepository $adviceRepository, SerializerInterface $serializer, ?int $id = null): JsonResponse
    {
        $month = $id ?? (int)date('n');
        $adviceList = $adviceRepository->findByMonth($month);
        $jsonAdviceList = $serializer->serialize($adviceList, 'json', ['groups' => 'getAdvices']);
        return new JsonResponse($jsonAdviceList, Response::HTTP_OK, [], true);
    }

    /**
     * Cette méthode permet d'insérer un nouveau conseil. 
     * Exemple de données : 
     * {
     *     "text": "Ceci est un conseil",
     *     "month": 3 // Pas encore opti
     * }
     *
     * @param Request $request
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $em
     * @param UrlGeneratorInterface $urlGenerator
     * @param MonthRepository $monthRepository
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('/api/conseil', name:"createAdvice", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un conseil')]
    public function createAdvice(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, MonthRepository $monthRepository, ValidatorInterface $validator): JsonResponse {

        $content = $request->toArray();
        
        $advice = new Advice();
        $advice->setText($content['text']);

        // On vérifie les erreurs
        $errors = $validator->validate($advice);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $monthValue = $content['month'] ?? (int)date('n');
        $monthEntity = $monthRepository->findByNumericValue($monthValue);

        if ($monthEntity) {
            $advice->addMonth($monthEntity);
        }

        $em->persist($advice);
        $em->flush();

        $jsonAdvice = $serializer->serialize($advice, 'json', ['groups' => 'getAdvices']);
        return new JsonResponse($jsonAdvice, Response::HTTP_CREATED, [], true);
    }
}